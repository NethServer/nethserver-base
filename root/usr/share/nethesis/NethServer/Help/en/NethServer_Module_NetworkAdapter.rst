=======
Network
=======

Change settings for network interfaces. Network interfaces in the system are automatically detected.

State
=====

Link
    Indicates whether the adapter is connected to any network device (eg. Ethernet
    cable connected to the switch).

Model
    Model of used network card.

Speed
    Indicates the speed that network card has negotiated (expressed in Mb/s).

Driver
    The driver the system uses to control the card.

Bus
    Network card physical bus (eg, PCI, USB).


Edit
====

Change settings of the network interface

Card
    Name of the network interface. This field can not be
    changed.

MAC Address
    Physical address of the network card. This field can not be
    changed.

Role
    The role indicates the destination of use of the interface, for example:

    * Green -> LAN Business
    * Red -> Internet, public IP

Mode
    Indicates which method will be used to assign the IP address to
    the network adapter. Possible values are *Static* and *DHCP*.

Static
    The configuration is statically allocated.

    * IP Address: IP address of the network card
    * Netmask: netmask of the network card
    * Gateway: server default gateway

DHCP
    The configuration is dynamically allocated (available only for
    RED interfaces)
