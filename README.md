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

[Contact us](mailto:kevin.darras@inrae.fr) for a collaboration.

## Quick start for developers

We use [Docker](https://www.docker.com) to run the app in your computer. We provide install.sh and run.sh files with all necessary commands, and a Makefile with extra commands to access the docker containers.

You need to install [docker](https://docs.docker.com/engine/install) and [docker-compose standalone](https://docs.docker.com/compose/install/standalone/) directly in your machine. Please read the documentation and follow the instructions carefully. We don't offer support for docker installation and configuration.

Important: this setup is intended for developing and testing purposes **ONLY**. It is in no way ready for production. Please read the _Server Installation_ section.

### Installation

```sh install.sh```

### Upgrading from older versions

If you're upgrading to this version and have existing data, you need to migrate your database from the old Docker named volume to a bind mount before running install.sh.

**Important: Back up your data first!**

```bash
# 1. Pull the latest changes
git pull origin audio-variable-access-point

# 2. Back up database
docker-compose exec database mysqldump -ubiosounds -pbiosounds --single-transaction --quick biosounds > backup.sql

# 3. Back up media files
tar -czf sounds_backup.tar.gz src/sounds/

# 4. Stop containers
docker-compose down

# 5. Switch to this branch (use -f to force overwrite Docker-created root-owned files)
git checkout -f audio-variable-access-point

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

# 14. Clear browser cache and localStorage (important!)
# In your browser console (F12 → Console):
# localStorage.clear()
# Then refresh the page (Ctrl+R or Cmd+R)

# 15. Test file uploads
# Try uploading a new recording to verify the worker is processing uploads correctly

# 16. Clean up old files (optional)
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

## Server installation

### With Docker

If you want to use Docker for your own server installation, please consult with a devOps engineer or someone with the necessary knowledge to manage it properly, depending on your hosting setup. 

The current Docker configuration [Dockerfile](src/Dockerfile) can be used for your preferred setup.

### Without Docker

Like any other web app, ecoSound-web can be installed without Docker (see Wiki).

### Configuration file

For both cases (with and without Docker), you'll need to set the configuration values in the [config.ini](src/config/config.ini) file, according to your server setup. 
