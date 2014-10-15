================
Network services
================

The table shows all network services running locally on the server.

Each service can have multiple TCP/UDP ports open.
Ports are open into the firewall accordingly to the `access` property.
The access property has three valid values:

* localhost: the service is accessible only from the server itself
* green: the service is accessible only from green interfaces and trusted networks
* green red: the service is accessible from local and external networks, but not from orange and blue
* custom: the service has custom access configured via `Allow hosts` or `Deny hosts`

When the service access is set to private or public, the administrator
can specify a list of hosts always allowed (or denied) to access the service. 

Edit
====

Edit the access of a network service.

Access from green and red networks
    Select this if the service must be from all networks, including Internet.
    For example: the mail server should be accessible from anyone.

Access only from green networks
    Select this if the service must be accessible only from local networks.
    For example: a critical database server should be accessible from LAN.

Access only from localhost
    Select this if the service must be accessible only from the server itself.
    For example: on a public VPS access to LDAP server should be denied from any network.

Allow hosts
    Specify a comma separated list of IP address or CIDR networks. Listed hosts will be always granted access to 
    the network service. (Applied only if access is public or private)

Deny hosts
    Specify a comma separated list of IP address or CIDR networks. Listed hosts will be always denied access to 
    the network service. (Applied only if access is public or private)


