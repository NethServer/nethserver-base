==============
Remote access
==============

It is possible to allow access to the web interface from computers in remote networks. Add allowed networks here.

Enabled hosts can access the web interface over HTTPS.

Web access
===========

Access to  configuration web interface.

Network address
    This is the address from which access to the web interface will be allowed
    web.

Network Mask
     To allow access to only one host, use as a subnet mask of 255.255.255.255.
    

SSH
===

Manage Secure Shell (SSH) server access.

Enable / Disable
    Enable / disable SSH access.

TCP port
    Enter the TCP port used for SSH access.

Accept connections from local networks
    SSH access enabled only for connections from local networks.
    
Accept connections from any network
    SSH access enabled for connections from any network.

Allow access for the root user
    Allow SSH access to the root user (administrative user).

Allow password authentication
    Allows access through SSH with simple password authentication.
    If not enabled, users will be able to authenticate
    only using a cryptographic key.
