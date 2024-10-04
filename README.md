[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.13889948.svg)](https://zenodo.org/records/13889948)

# ecoSound-web

## Description

Web application for ecoacoustics to manage, navigate, visualise, annotate, and analyse soundscape recordings.

## Credits and license

Designed by [Kevin Darras](http://kevindarras.weebly.com/index.html), developed by [Noemi Perez](https://github.com/nperezg) and Dilong Liu. ecoSound-web was forked from [BioSounds](https://github.com/nperezg/biosounds) and is licensed under the [GNU General Public License, v3](https://www.gnu.org/licenses/gpl-3.0.en.html).

The corresponding updatable scientific publication is in [F1000Research](https://f1000research.com/articles/9-1224/v2).

## Quick start for end users

A working instance of ecoSound-web can be accessed [here](https://ecosound-web.de/) with limited functionality within the open collections.

You may learn about the basic functionality in the user guide (see Wiki).

[Contact us](mailto:kdarras@gwdg.de) for a collaboration.

## Quick start for developers

We use [Docker](https://www.docker.com) to run the app in your computer. We provide install.sh and run.sh files with all necessary commands, and a Makefile with extra commands to access the docker containers.

You need to install [docker](https://docs.docker.com/engine/install) and [docker-compose standalone](https://docs.docker.com/compose/install/standalone/) directly in your machine. Please read the documentation and follow the instructions carefully. We don't offer support for docker installation and configuration.

Important: this setup is intended for developing and testing purposes **ONLY**. It is in no way ready for production. Please read the _Server Installation_ section.

### Installation

```sh install.sh```

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
