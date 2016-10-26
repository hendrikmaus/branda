# Branda
An API description mock server.

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg)](https://php.net/)
[![Build Status](https://travis-ci.org/hendrikmaus/branda.svg?branch=master)](https://travis-ci.org/hendrikmaus/branda)
[![codecov](https://codecov.io/gh/hendrikmaus/branda/branch/master/graph/badge.svg)](https://codecov.io/gh/hendrikmaus/branda)
[![Code Climate](https://codeclimate.com/github/hendrikmaus/branda/badges/gpa.svg)](https://codeclimate.com/github/hendrikmaus/branda)

## Note
Branda is currently **experimental**!  
It only supports API Blueprint as we speak.

## Installation And Usage

### Docker
If you are familiar with docker, it is by far the most comfortable way to use branda:

> Mac users: make sure that your files can be made available to docker.
> E.g.: with docker-machine-nfs, mount the respective folder or you `-v` won't work with docker

```bash
docker run -it --name "branda" --rm -p 8000:8000 \
  -v $(pwd):/appdata hendrikmaus/branda \
  mock 0.0.0.0 -f /appdata/your-service.apib
```

Let's get into the details of that command:

- `docker run -it --name "branda" --rm`
    - `-t` Allocate a pseudo-tty
    - `-i` Keep STDIN open even if not attached
    - `--name "branda"` Name of the container
    - `--rm` Cleanup when the container exits
    
- `-p 8000:8000`
    - Port mapping
    - branda will listen on port 8000 by default, but you can change it using an option
    - Note for Mac users: you will expose the port on the IP of your vm solution, e.g. docker-machine

- `-v $(pwd):/appdata hendrikmaus/branda \`
    - `-v` Mount current working directory into `/appdata` folder inside the container
    - `hendrikmaus/branda` is the image name (and tag)

- `mock 0.0.0.0 -f /appdata/your-service.apib`
    
    These are the actual arguments and options passed to branda.
make sure to listen on the public interface.
When passing your file into it, remember it sits inside of `/appdata` in the container
and `/appdata` is equal to the structure of your current working directory

#### Stopping Branda
`docker stop branda`

### Source

> **Pre-Requisites:**  
> Installing branda from source means your machine must have PHP >= 7 and Cpp tools to compile Drafter

The recommended way to install branda is by using composer.  
Since there currently is a dependency on drafter, you have to add this to your `scripts` section in composer.json:

```json
  "extra": {
    "drafter-installer-tag": "v3.1.3"
  },
  "scripts": {
    "install-drafter": "Hmaus\\Drafter\\Installer::installDrafter",
    "post-install-cmd": [
      "@install-drafter"
    ],
    "post-update-cmd": [
      "@install-drafter"
    ]
  }
```

Now you can require branda itself:
```bash
composer require hmaus/branda
```

> You have to require dev-master since there is no tag on the experimental version yet

## Usage
The simplest example to fire up a mock server:

```bash
vendor/bin/branda mock --file "your-service.apib"
```

> When installing using composer, branda will install to your `bin` folder, `vendor/bin` by default
