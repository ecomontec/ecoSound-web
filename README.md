[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.13889948.svg)](https://zenodo.org/records/13889948)

# ecoSound-web

## Description

Web application for ecoacoustics to manage, navigate, visualise, annotate, and analyse soundscape recordings.

## Credits and license

Designed by [Kevin Darras](http://kevindarras.weebly.com/index.html), developed by [Noemi Perez](https://github.com/nperezg) and Dilong Liu. ecoSound-web was forked from [BioSounds](https://github.com/nperezg/biosounds) and is licensed under the [GNU General Public License, v3](https://www.gnu.org/licenses/gpl-3.0.en.html).

The corresponding updatable scientific publication is in [F1000Research](https://f1000research.com/articles/9-1224/v3).

## Quick start for end users

A working instance of ecoSound-web can be accessed [here](https://ecosound-web.de/ecosound_web/) with limited functionality within the open collections.

You may learn about the basic functionality in the user guide (see Wiki).

[Contact us](mailto:kevin.darras@inrae.fr) for collaboration.

## Quick start for developers

We use [Docker](https://www.docker.com) to run the app in your computer. We provide install.sh and run.sh files with all necessary commands, and a Makefile with extra commands to access the docker containers.

You need to install [docker](https://docs.docker.com/engine/install) and [docker-compose standalone](https://docs.docker.com/compose/install/standalone/) directly in your machine. Please read the documentation and follow the instructions carefully. We don't offer support for docker installation and configuration.

Important: this setup is intended for developing and testing purposes **ONLY**. It is in no way ready for production. Please read the _Server Installation_ section.

### System Requirements

**Disk Space**: Ensure a minimum of 40 GB is available before installation, particularly on the partition holding Docker data (typically `/var`)

This is due to Docker images containing machine learning libraries (TensorFlow 2.20.0 + PyTorch 2.4.1) and audio processing tools (~22GB for biosounds-apache image alone).

**Network Access**: The AI model Insect-CNN requires internet access to Hugging Face (`https://huggingface.co`) during first use to download model files. Models are cached locally after the first download. If your server has restricted outbound HTTPS access, you may need to:
- Whitelist `huggingface.co` (port 443) in your firewall
- Or manually transfer model cache files from another machine (see Troubleshooting section)

### Installation

```sh install.sh```

### Running the Application

```sh run.sh```

This starts all services and launches the queue worker with automatic restart capability. **Run this script only once** (typically at server startup). The worker processes jobs sequentially from the queue. If the worker crashes, it will automatically restart within 5 seconds, ensuring continuous processing without manual intervention.

**Note:** Do not run `run.sh` multiple times - this would create duplicate workers processing jobs in parallel.

### Upgrading to latest version (standard upgrade)

**For installations where data are already in dedicated folders** (mysql/, sounds/, sound_images/, project_images/):

```bash
# 1. Create backup directory
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# 2. Backup database (using root user which has necessary privileges)
docker-compose exec -T database mysqldump -uroot -proot \
  --single-transaction --quick biosounds > "$BACKUP_DIR/biosounds_db.sql"

# 3. Save git modifications (if any)
git diff > "$BACKUP_DIR/git-modifications.diff"

# 4. Stop services
docker-compose down

# 5. Move data directories to backup (saves disk space vs copying)
# Note: mysql/ is owned by root, media dirs by www-data - need sudo
sudo mv mysql "$BACKUP_DIR/"
sudo mv sounds "$BACKUP_DIR/" 2>/dev/null || true
sudo mv sound_images "$BACKUP_DIR/" 2>/dev/null || true
sudo mv project_images "$BACKUP_DIR/" 2>/dev/null || true

# Verify backup contents
ls -la "$BACKUP_DIR/"

# 6. Clean git working directory
git clean -fd -e "backup_*/"
git reset --hard HEAD

# 7. Update code
git pull origin master  # or your branch name

# 8. Restore data directories
sudo mv "$BACKUP_DIR/mysql" ./
sudo mv "$BACKUP_DIR/sounds" ./ 2>/dev/null || true
sudo mv "$BACKUP_DIR/sound_images" ./ 2>/dev/null || true
sudo mv "$BACKUP_DIR/project_images" ./ 2>/dev/null || true

# 9. Run installation (safe to re-run - skips DB init if tables exist)
bash install.sh

# 10. Start services
bash run.sh

# 11. Verify everything works
docker-compose ps
docker-compose exec database mysql -ubiosounds -pbiosounds biosounds -e "SHOW TABLES;"
```

**Notes:** 
- The install.sh script is safe to re-run - it detects existing tables and skips database initialization
- Data directories are moved (not copied) to save disk space
- Keep the backup directory until you've verified the upgrade succeeded

If you encounter issues, restore from backup and review the Troubleshooting section.

### Upgrading from older versions

If you're upgrading to this version and have existing data, you need to migrate your database from the old Docker named volume to a bind mount before running install.sh.

**Important: Back up your data first!**

```bash
# 1. Pull the latest changes
git pull origin merged-terraform-audio

# 2. Back up database (using root user which has necessary privileges)
docker-compose exec -T database mysqldump -uroot -proot --single-transaction --quick biosounds > backup.sql

# 3. Back up media files
tar -czf sounds_backup.tar.gz src/sounds/

# 4. Stop containers
docker-compose down

# 5. Switch to this branch (use -f to force overwrite Docker-created root-owned files)
git checkout -f merged-terraform-audio

# 6. Initialize new data directories
bash init-data-dirs.sh

# 7. Start containers and initialize database schema
docker-compose up -d

# 8. Wait for database to be ready
docker-compose exec apache bash -c 'while ! (nc -z database 3306); do echo "Database is not ready..."; sleep 2; done;'

# 9. Initialize database schema (required for fresh database)
docker-compose exec -i database mysql -ubiosounds -pbiosounds biosounds < init.sql
docker-compose exec -i database mysql -ubiosounds -pbiosounds biosounds < data.sql
docker-compose exec -i database mysql -ubiosounds -pbiosounds biosounds < gadm.sql
docker-compose exec -i database mysql -ubiosounds -pbiosounds biosounds < world_seas.sql

# 10. Restore your backed-up database data (optional, if you have backup.sql from step 2)
# Only run this if you want to restore your previous recordings and data
# cat backup.sql | docker-compose exec -T database mysql -uroot -proot biosounds

# 11. Restore media files to new folders
tar -xzf sounds_backup.tar.gz
sudo mv src/sounds/sounds/* sounds/ 2>/dev/null || true
sudo mv src/sounds/images/* sound_images/ 2>/dev/null || true
sudo mv src/sounds/projects/* project_images/ 2>/dev/null || true

# 12. Verify database and media
docker-compose exec database mysql -ubiosounds -pbiosounds biosounds -e "SHOW TABLES;"
ls -la sounds/ sound_images/ project_images/

# 13. Restart the queue worker to reconnect to migrated database
docker-compose restart apache
# Wait for Apache to be ready
docker-compose exec apache bash -c 'while ! (nc -z database 3306); do echo "Database is not ready..."; sleep 2; done;'
# Start the worker process
docker-compose exec -T -u www-data apache nohup php worker.php > files_update.log 2>&1 &

# 14. Test file uploads
# Try uploading a new recording to verify the worker is processing uploads correctly

# 15. Clean up old files (optional)
rm -rf src/sounds
docker volume rm biosounds-mysql 2>/dev/null || true
```

If you have no existing data, simply run `./install.sh` normally.

### Run

```sh run.sh```

### Using ecoSound-web

Open http://localhost:8080

Log in with username: admin, password: Administrator20

Important: please **change the password** of this administrator user or **delete** it once you have ecoSound-web running on production and have your own admin users.

### Stop

```docker-compose stop```

### Optional: clean restart (removing containers)

```docker container prune```

## Troubleshooting

### Insects CNN Model Download Issues

The Insect CNN model (`insects-base-cnn10-96k-t`) is downloaded automatically from HuggingFace on first use. However, some servers may have network restrictions that prevent access to `huggingface.co`.

**Symptoms:**
- Warning during install.sh: `⚠ WARNING: Cannot access HuggingFace.co`
- Network test shows: `curl: (35) OpenSSL SSL_connect: Connection reset by peer`
- Insect model analysis jobs fail with "Invalid hugging face repo id" error

**Solution : Manual Model Transfer** (For Restricted Networks)

Confirm that your server cannot access HuggingFace due to network restrictions:
```bash
# From inside the container
docker-compose exec apache curl -I https://huggingface.co
```

You'll then need to manually transfer the model from a machine with internet access:

**Step 1: On a machine with internet access (that has ecoSound-web installed):**

```bash
# Trigger model download by running autrainer inference (it will cache automatically)
# This creates a small test audio file and runs inference to trigger the download
docker-compose exec apache bash -c "echo 'Downloading insect model...' && \
  mkdir -p /tmp/test_input /tmp/test_output && \
  python3 -c 'import numpy as np; import soundfile as sf; sf.write(\"/tmp/test_input/test.wav\", np.zeros(96000), 96000)' && \
  autrainer inference hf:AlexanderGbd/insects-base-cnn10-96k-t /tmp/test_input /tmp/test_output -sr 96000 && \
  rm -rf /tmp/test_input /tmp/test_output"

# Export the cached model (model is cached in /root/.cache by autrainer)
docker-compose exec apache tar -czf /tmp/torch-cache.tar.gz -C /root/.cache torch
docker cp $(docker-compose ps -q apache):/tmp/torch-cache.tar.gz ./torch-cache.tar.gz
```

**Step 2: Transfer to your restricted server:**

```bash
# Copy the archive to your server
scp torch-cache.tar.gz user@yourserver:/tmp/

# On the restricted server, import the model
docker cp /tmp/torch-cache.tar.gz $(docker-compose ps -q apache):/tmp/
docker-compose exec apache mkdir -p /root/.cache
docker-compose exec apache tar -xzf /tmp/torch-cache.tar.gz -C /root/.cache/
docker-compose exec apache rm /tmp/torch-cache.tar.gz

# Copy to the location where the application expects it (/var/www/.cache)
docker-compose exec apache mkdir -p /var/www/.cache/torch/hub/autrainer
docker-compose exec apache cp -r /root/.cache/torch/hub/autrainer/AlexanderGbd--insects-base-cnn10-96k-t--main /var/www/.cache/torch/hub/autrainer/
docker-compose exec apache chown -R www-data:www-data /var/www/.cache

# Clean up local temp file
rm /tmp/torch-cache.tar.gz
```

**Step 3: Verify installation:**

```bash
# Check if model directory exists in the expected location
docker-compose exec apache ls -la /var/www/.cache/torch/hub/autrainer/

# You should see: AlexanderGbd--insects-base-cnn10-96k-t--main
```

## Server installation

For production deployment using Docker, the current [docker-compose.yml](docker-compose.yml) and [Dockerfile](src/Dockerfile) can be adapted for your server environment. Consult with a DevOps engineer or system administrator familiar with Docker deployments to properly configure security, networking, reverse proxies, SSL certificates, and backups according to your hosting requirements.

**Configuration:** Regardless of your deployment method, configure the [config.ini](src/config/config.ini) file according to your server setup.
