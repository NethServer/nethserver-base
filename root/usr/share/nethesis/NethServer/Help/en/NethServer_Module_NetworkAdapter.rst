=======
Network
=======

The table shows the list of physical (eth, wlan, ...) and logical
(bridge, bond, vlan, alias) interfaces present on the system.

:guilabel:`Role` column

   indicates the destination use of the interface, for example:	  
   
   * Green: local LAN
   * Blue: guest network
   * Orange: DMZ network
   * Red: Internet, public IP

:guilabel:`New interface` button

   Starts the procedure that creates logical interfaces. Follow
   on screen instructions, then confirm.

:guilabel:`Edit` action

   Change device settings.
   
:guilabel:`Create IP alias` action

   Create a new IP alias for the device.

:guilabel:`Release role` action

   Release the role assigned to the device.

:guilabel:`Proxy settings` button

   Configure an upstrem proxy.
   This configuration is used from YUM and Squid (if installed).


Multi WAN
=========

Configuration for multiple Internet connections.

Link name
     A name to identify the connection (ISP). Max 5 characters.

Link weight
     The "weight" of the connection.
     Traffic will be routed proportionally to the weight: higher weight means more traffic.
     A provider with a weight of 100 will receive twice the traffic of one with weight 50.
     Please, assign weights accordingly to connection bandwidth.
     When using active-backup mode, the weight determines the use of the line.
     If the first provider has weight 100 and the second has weight 50,
     the traffic is always sent to the first provider. The second one will be used only if first provider goes down.

Proxy settings
==============

Configuration of upstream HTTP/HTTPS proxy server.

HTTP(S) proxy
    Host name or IP address of the upstream proxy server.

Port
    Port of the the upstream proxy server.

User name
    If the upstream proxy is authenticated, enter a user name

Password
    If the upstream proxy is authenticated, enter a password

