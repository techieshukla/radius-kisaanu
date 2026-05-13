FROM debian:bookworm-slim

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
      freeradius \
      freeradius-utils \
      freeradius-mysql \
      ca-certificates \
      tzdata \
    && rm -rf /var/lib/apt/lists/*

COPY freeradius/sites-enabled/default /etc/freeradius/3.0/sites-enabled/default
COPY freeradius/sites-enabled/inner-tunnel /etc/freeradius/3.0/sites-enabled/inner-tunnel
COPY freeradius/policy/daily_quota /etc/freeradius/3.0/policy.d/daily_quota
COPY freeradius/clients.conf.template /tmp/clients.conf.template
COPY scripts/freeradius-entrypoint.sh /usr/local/bin/freeradius-entrypoint.sh

RUN chmod +x /usr/local/bin/freeradius-entrypoint.sh

EXPOSE 1812/udp 1813/udp
ENTRYPOINT ["/bin/sh", "/usr/local/bin/freeradius-entrypoint.sh"]
