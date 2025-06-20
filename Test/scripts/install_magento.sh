#!/usr/bin/env bash

#/**
#* Bolt magento2 plugin
#*
#* NOTICE OF LICENSE
#*
#* This source file is subject to the Open Software License (OSL 3.0)
#* that is bundled with this package in the file LICENSE.txt.
#* It is also available through the world-wide-web at this URL:
#* http://opensource.org/licenses/osl-3.0.php
#*
#* @category   Bolt
#* @package    Bolt_Boltpay
#* @copyright  Copyright (c) 2017-2023 Bolt Financial, Inc (https://www.bolt.com)
#* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
#*/

set -e
set -u
set -x

trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

composer show -i
cd ../magento

echo "Waiting for DB..."
while ! mysqladmin ping -h 127.0.0.1 --silent; do
    sleep 1
done

# TODO(vitaliy): Investigate the root of the issue. See https://github.com/magento/magento2/issues/24650
if [ -f "vendor/magento/module-inventory-catalog/etc/communication.xml" ]; then
    sed -i 's/is_synchronous="false"//g' vendor/magento/module-inventory-catalog/etc/communication.xml
fi

echo "Installing Magento..."
mysql -uroot -h 127.0.0.1 -e "CREATE DATABASE magento2;"
if [ "${MAGENTO_VERSION}" == "2.4.8" ]; then
    # For Magento 2.4.8 — OpenSearch
    php bin/magento setup:install -q \
        --language="en_US" \
        --timezone="UTC" \
        --currency="USD" \
        --db-host=127.0.0.1 \
        --db-user=root \
        --base-url="http://magento2.test/" \
        --admin-firstname="Dev" \
        --admin-lastname="Bolt" \
        --backend-frontname="backend" \
        --admin-email="admin@example.com" \
        --admin-user="admin" \
        --use-rewrites=1 \
        --admin-use-security-key=0 \
        --admin-password="123123q" \
        --magento-init-params="MAGE_MODE=developer" \
        --search-engine=opensearch \
        --opensearch-host=localhost \
        --opensearch-port=9200

elif [ "${MAGENTO_VERSION}" == "2.4.2" ]; then
    # For Magento 2.4.2 — Elasticsearch 7
    sudo service elasticsearch start
    sudo php bin/magento setup:install -q \
        --language="en_US" \
        --timezone="UTC" \
        --currency="USD" \
        --db-host=127.0.0.1 \
        --db-user=root \
        --base-url="http://magento2.test/" \
        --admin-firstname="Dev" \
        --admin-lastname="Bolt" \
        --backend-frontname="backend" \
        --admin-email="admin@example.com" \
        --admin-user="admin" \
        --use-rewrites=1 \
        --admin-use-security-key=0 \
        --admin-password="123123q" \
        --magento-init-params="MAGE_MODE=developer" \
        --search-engine=elasticsearch7 \
        --elasticsearch-host=localhost \
        --elasticsearch-port=9200

else
    # For others
    php -dmemory_limit=5G bin/magento setup:install -q \
        --language="en_US" \
        --timezone="UTC" \
        --currency="USD" \
        --db-host=127.0.0.1 \
        --db-user=root \
        --base-url="http://magento2.test/" \
        --admin-firstname="Dev" \
        --admin-lastname="Bolt" \
        --backend-frontname="backend" \
        --admin-email="admin@example.com" \
        --admin-user="admin" \
        --use-rewrites=1 \
        --admin-use-security-key=0 \
        --admin-password="123123q" \
        --magento-init-params="MAGE_MODE=developer"
fi

#COMPOSER_MEMORY_LIMIT=5G composer require --dev "mikey179/vfsstream:^1.6"
