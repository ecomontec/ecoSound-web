#!/bin/sh

# Create Rabbitmq user
( rabbitmqctl wait --timeout 60 $RABBITMQ_PID_FILE; \
rabbitmq-plugins enable rabbitmq_management ; \
wget -O /usr/local/bin/rabbitmqadmin
http://127.0.0.1:15672/cli/rabbitmqadmin ; \
chmod +x /usr/local/bin/rabbitmqadmin ; \
rabbitmqadmin declare queue name=$QUEUE_NAME ; \
rabbitmqctl add_user $RABBITMQ_USER $RABBITMQ_PASSWORD 2>/dev/null ; \
rabbitmqctl set_user_tags $RABBITMQ_USER administrator ; \
rabbitmqctl set_permissions -p / $RABBITMQ_USER  ".*" ".*" ".*" ; \
rabbitmqctl delete_user "guest" ; \
echo "*** Log in the WebUI at port 15672 (example: http:/localhost:15672) ***") &

# $@ is used to pass arguments to the rabbitmq-server command.
# For example if you use it like this: docker run -d rabbitmq arg1 arg2,
# it will be as you run in the container rabbitmq-server arg1 arg2
rabbitmq-server $@