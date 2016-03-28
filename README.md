# observium-libre-port-migration
Migrate port configurations from Observium to LibreNMS

Migrates the following port configurations from Observium to LibreNMS

- Ignore
- Disabled
- Detailed
- Deleted

The script assumes that the devices are already added to LibreNMS and have gone through port discovery.

Populate mydevices.txt with the list of devices to replicate port configurations for. 