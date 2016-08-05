==============
Static routes
==============

This page can be used to configure static routes for the network traffic.
For example, static routes cane used
to reach private networks connected via dedicated lines.

Remember to add the same network to the :guilabel:`Trusted networks` panel,
if hosts on the remote network should access local services.

Create / Modify
===============

Create a new route to a remote network.

Network address
    Destination network in CIDR format or with the special value *default*.
    If *default* is used, the route will create a default gateway rule for the given interface.
    Please note that the gateway specified inside the green interface has precedence. 

Router address
    Address of the gateway used to reach the specified network,
    this field is not required.

Device
    If needed, select a evice to force the traffic into chosen network interface.

Metric
    Metric is used to determine whether one route should be chosen over another.

Description
    A free text field to record any annotation.

