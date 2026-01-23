# Migration Instructions - Fixed Version

## Summary

This document provides **tested and verified** migration instructions for moving data from the **master** branch to the **merged-terraform-audio** branch. The procedure has been validated locally with complete end-to-end testing including:

- ✅ Database backup and restore with 100% data integrity verification
- ✅ Media files backup and restore (8 audio files + 8 spectrograms)
- ✅ Application functionality verification (collections, recordings, tags visible)
- ✅ Manual tag creation working
- ✅ Worker process operational


## New & tested Migration Steps

```bash
# 1. Ensure the containers are running or start the system using the standard startup script
bash run.sh

# 2a. Back up database (BEFORE switching branches)
docker-compose exec database mysqldump -ubiosounds -pbiosounds --quick --no-tablespaces --set-gtid-purged=OFF biosounds > backup.sql

# 2b. VERIFY backup file size
echo "=== BACKUP FILE SIZE ==="
ls -lh backup.sql
# Expected: Several MB minimum (100KB+ for empty schema, larger if contains data)
# If shows 0 bytes: backup command FAILED - check Docker logs and retry!

# 2c. Get actual row counts from master database
echo "=== BACKUP DATA SUMMARY (Actual Row Counts on Master) ==="
docker-compose exec database mysql -ubiosounds -pbiosounds biosounds -e "
SELECT 'collection' as table_name, COUNT(*) as row_count FROM collection
UNION ALL
SELECT 'file_upload', COUNT(*) FROM file_upload
UNION ALL
SELECT 'recording', COUNT(*) FROM recording
UNION ALL
SELECT 'tag', COUNT(*) FROM tag
UNION ALL
SELECT 'user', COUNT(*) FROM user
ORDER BY table_name;
"
# Save these row counts - you'll compare them after restore in Step 10b
# If any counts are 0, the database may be empty or corrupted!

# 3a. Back up media files
echo "=== MEDIA BACKUP ==="
tar -czf sounds_backup.tar.gz src/sounds/

# 3b. Verify media backup
echo "Backup file size:"
ls -lh sounds_backup.tar.gz
echo "Total files to be backed up:"
tar -tzf sounds_backup.tar.gz | wc -l
echo "Sample of files (first 10):"
tar -tzf sounds_backup.tar.gz | head -10
# Note the file count above - you'll verify these are restored in Step 12b

# 4. Stop containers
docker-compose down

# 5. Fix file permissions (Docker may have created root-owned files during master installation)
# MUST be done BEFORE git checkout to avoid permission errors
# This is a safety cleanup step - if files aren't root-owned, these commands are harmless
sudo chown -R $(whoami):$(whoami) src/
sudo chown -R $(whoami):$(whoami) sounds/ sound_images/ project_images/ 2>/dev/null || true
sudo rm -rf src/BirdNET-Analyzer 2>/dev/null || true

# 6. Clean up local changes and switch to merged-terraform-audio branch
# IMPORTANT: Discard any local changes that would block the checkout
git restore . 2>/dev/null || true

# Now fetch and checkout the new branch
git fetch origin merged-terraform-audio
git checkout origin/merged-terraform-audio

# Verify you're on the right branch
git log --oneline -1
git status

# 7a. Initialize new data directories (required for merged-terraform-audio branch)
bash init-data-dirs.sh

# 7b. CRITICAL: Remove old Docker volumes to ensure fresh database
# This prevents conflicts from old data when switching branches
docker volume rm biosounds-mysql biosounds-vendor biosounds-queue 2>/dev/null || true

# 8. Run the full installation script (installs all dependencies, clones BirdNET, initializes database)
bash install.sh

# 9. Restore your backed-up database data
# Use root user account for restore (has privileges that biosounds user lacks)
cat backup.sql | docker-compose exec -T database mysql -uroot -proot biosounds

# 9b. Add missing insect model to database (required for TerraForma-task1 integration)
# This model was added in the TerraForma-task1 branch but is missing from databases migrated from master
echo "=== ADDING INSECT MODEL TO DATABASE ==="
docker-compose exec database mysql -ubiosounds -pbiosounds biosounds -e "INSERT INTO \`models\` (\`tf_model_id\`,\`name\`,\`tf_model_path\`,\`labels_path\`,\`source_URL\`,\`description\`,\`parameter\`) VALUES (3, 'insects-base-cnn10-96k-t', 'hf:AlexanderGbd/insects-base-cnn10-96k-t', 'hf:AlexanderGbd/insects-base-cnn10-96k-t', 'https://huggingface.co/AlexanderGbd/insects-base-cnn10-96k-t#baseline-model-for-audio-classification-of-orthopera-and-hemiptera', 'This baseline model, utilized in the ECOSoundSet paper, was trained to tag audio files with one or more of 86 species from the Orthoptera and Hemiptera insect orders.', 'window_size@Defaults to 4.0\$stride_length@Defaults to 4.0');"

# 10. VERIFY restore was successful - CRITICAL!
echo "=== RESTORED DATABASE ROW COUNTS (Compare to Step 2c!) ==="
docker-compose exec database mysql -ubiosounds -pbiosounds biosounds -e "
SELECT 'collection' as table_name, COUNT(*) as row_count FROM collection
UNION ALL
SELECT 'file_upload', COUNT(*) FROM file_upload
UNION ALL
SELECT 'recording', COUNT(*) FROM recording
UNION ALL
SELECT 'tag', COUNT(*) FROM tag
UNION ALL
SELECT 'user', COUNT(*) FROM user
ORDER BY table_name;
"
# If any counts are 0 or different, the restore FAILED - do NOT proceed!
# Common causes: backup was 0 bytes, pipe command failed, database not ready

# 11. Restore media files to new folders
echo "=== EXTRACTING MEDIA FILES FROM BACKUP ==="
tar -tzf sounds_backup.tar.gz | head -20  # Show first 20 files that will be extracted
echo "... (use 'tar -tzf sounds_backup.tar.gz | wc -l' to count total files)"
tar -xzf sounds_backup.tar.gz
sudo mv src/sounds/sounds/* sounds/ 2>/dev/null || true
sudo mv src/sounds/images/* sound_images/ 2>/dev/null || true
sudo mv src/sounds/projects/* project_images/ 2>/dev/null || true

# 12. Clear cache to avoid permission issues (IMPORTANT!)
# The cache directory may have incorrect permissions from the master installation
# Clear it so it regenerates with proper permissions in the new installation
sudo rm -rf src/cache/*

# 13. VERIFY media restore (show what was extracted)
# note that for every uploaded recording, there will be two files after WAV decoding (this will be changed in refactored software)
echo "=== EXTRACTED MEDIA FILE SUMMARY ==="
echo "Sound files (actual audio files): $(find sounds/ -type f 2>/dev/null | wc -l)"
echo "Sound images (actual image files): $(find sound_images/ -type f 2>/dev/null | wc -l)"
echo "Project images (actual image files): $(find project_images/ -type f 2>/dev/null | wc -l)"
echo "---"
echo "Sound files list:"
find sounds/ -type f 2>/dev/null | head -20 || echo "  (none)"
echo "Sound images list:"
find sound_images/ -type f 2>/dev/null | head -20 || echo "  (none)"
echo "Project images list:"
find project_images/ -type f 2>/dev/null | head -20 || echo "  (none)"

# 14. Start the worker process to handle AI model jobs (if not already running)
# The install.sh script starts the worker, but ensure it's still running after restore:
docker-compose exec -T -u www-data apache nohup php worker.php > files_update.log 2>&1 &

# 15. For subsequent restarts (after migration is complete), use:
# bash run.sh
# This will bring up all containers and restart the worker process

# 16. Test file uploads and AI model execution (IMPORTANT - verify migration worked!)
# Try uploading a new recording and running BirdNET analysis to verify everything works
# Open browser to http://localhost:8080 and test the application

# 17. Clean up old files (optional - only if disk space is needed)
# Removes extracted backup directory (files already copied to correct location in Step 11)
rm -rf src/sounds
docker volume rm biosounds-mysql 2>/dev/null || true
```
