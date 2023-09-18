FROM redis:6.2

COPY redis/redis.conf /usr/local/etc/redis/redis.conf
COPY redis/users.acl /etc/redis/users.acl

COPY redis/entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD [ "redis-server", "/usr/local/etc/redis/redis.conf" ]
