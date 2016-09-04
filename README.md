# Branda
An API description mock server.

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![Build Status](https://travis-ci.org/hendrikmaus/branda.svg?branch=master)](https://travis-ci.org/hendrikmaus/branda)
[![codecov](https://codecov.io/gh/hendrikmaus/branda/branch/master/graph/badge.svg)](https://codecov.io/gh/hendrikmaus/branda)
[![Code Climate](https://codeclimate.com/github/hendrikmaus/branda/badges/gpa.svg)](https://codeclimate.com/github/hendrikmaus/branda)

## Note
Branda is currently **experimental**!  
It only supports API Blueprint as we speak.

## Installation
The recommended way to install branda is by using composer.  
Since there currently is a dependency on drafter, you have to add this to your `scripts` section in composer.json:

```json
"install-drafter": [
  "if ! [[ -d ext/drafter ]]; then echo \"### Installing drafter to ./ext; drafter bin to ./vendor/bin/ ###\"; fi",
  "if ! [[ -d ext/drafter ]]; then git clone --branch v3.1.0 --recursive https://github.com/apiaryio/drafter.git ext/drafter; fi",
  "if ! [[ -d vendor/bin ]]; then mkdir -p vendor/bin; fi",
  "if ! [[ -f vendor/bin/drafter ]]; then cd ext/drafter && ./configure && make drafter; fi",
  "if ! [[ -f vendor/bin/drafter ]]; then cd vendor/bin && ln -s ../../ext/drafter/bin/drafter drafter; fi"
],
"post-install-cmd": [
  "@install-drafter"
],
"post-update-cmd": [
  "@install-drafter"
]
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
