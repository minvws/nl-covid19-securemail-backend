#!/usr/bin/env bash

if [ -f "/etc/redis/users.acl.bck"]; then
        cp /etc/redis/users.acl.bck /etc/redis/users.acl
    else
        cp /etc/redis/users.acl /etc/redis/users.acl.bck
    fi

if [ ${REDIS_USERNAME} != '' ]; then
        echo "user default off" >> /etc/redis/users.acl
        echo "user ${REDIS_USERNAME} on >${REDIS_PASSWORD} ~* &* +@all" >> /etc/redis/users.acl
    fi

exec "$@"
