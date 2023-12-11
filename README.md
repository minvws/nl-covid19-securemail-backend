# COVID-19 Secure Mail - Backend

## Introduction

This repository contains the backend implementation of the Dutch COVID-19 Secure Mail project.

- The backend is located in the repository you are currently viewing.
- The <related> can be found here: <related repo>

## Overview

The backend consists of the following services:

- messagebox: contains the publicly accessible securemail-frontend
- bridge: bridges requests from the messagebox to the messaging-app via Redis
- messaging-app: contains all the message-data
- messaging-app-queue-worker: processes incoming messages and sends notifications by email
- messaging-api: contains the api to send messages and retrieve statusupdates

### Api authentication

Most api-requests need authentication, so use `Bearer <token>` header containing the jwt-token. Note that the
combination of the platform-identifier and the secret to sign the token  (or comma separated multiple sets) should be
set in the env-var `MESSAGING_API_JWT_SECRETS`, e.g. `platform:jwtsecret`

Required header fields for the jwt-token:

- `alg` with the algorithm, supported values are: `HS256`, `HS512` and `HS384`
- `typ` with value `JWT`
- `kid` with the platform-identifier, e.g. `platform`

Required body fields for the jwt-token:

- `iat` with the current timestamp
- `exp` with the expiration timestamp (lifetime is configurable with env-vars, default is 60 seconds)

## Local development setup

Prerequisites: A working Docker environment

Steps to run a local development environment:

- Clone the repository to your local environment
- Run: `git submodule update --init --recursive`
- Create an `.env` file (you can create a copy of `.env.example` to get started).
- Run `bin/setup-dev` to set up the environment (initialize database, install dependencies).

If the command has completed successfully, you will have multiple webservers running (on localhost):

- The webinterface of the mailcatch-application will run on port 8025
- The api of the messaging-app will run on port 8081 (used by the messagebox)
- The messaging-api will run on port 8082 (to send messages and get statusupdates)
- The messagbox will be available on port 8083 (http) and port 8084 (https)

* ### Useful commands
* To start the dev-environment, use `bin/docker-compose-dev up -d`
* To rebuild the dev-environment (reset all data), use `bin/reset-dev`
* To run phpcs, use `bin/phpcs`
* To run phpunit, use `bin/phpunit`
* watch frontend code with `bin/messagebox-npm-watch`
* watch frontend code and start development server on port 9200 with `bin/messagebox-npm-hot`

## Development & Contribution process

The development team works on the repository in a private fork (for reasons of compliance with existing processes) and shares its work as often as possible.

If you plan to make non-trivial changes, we recommend to open an issue beforehand where we can discuss your planned changes.
This increases the chance that we might be able to use your contribution (or it avoids doing work if there are reasons why we wouldn't be able to use it).

Note that all commits should be signed using a gpg key.

## Release process

### Step 1. Create the release branch

You can skip this step, if you want to make the release from the `main` branch directly.

For each (minor) release you first need to create a release branch (e.g. `release/0.3.x`) from the `main` branch.

Before creating a new branch, pull the changes from upstream. Your main needs to be up to date.

```bash
git pull
git checkout -b "release/0.3.x"
git push -u origin "release/0.3.x"
```

> This step only need to be done once for each release version/branch.

### Step 2. Create a Github Release or Git tag

Create and push tag:

```bash
VERSION=0.0.1
git tag -a $VERSION -m "release $VERSION"
git push origin $VERSION
```

> This will create a Github Release and Build and Push Docker Images and Helm Charts.

Docker Images will be pushed to private Github Container Registry:
`ghcr.io/minvws/nl-covid19-securemail-backend-private/[APP_NAME]:[VERSION]`

We will use tags with version numbers only. For the test environment we can use semver's so that a patch version will be deployed automatically.
