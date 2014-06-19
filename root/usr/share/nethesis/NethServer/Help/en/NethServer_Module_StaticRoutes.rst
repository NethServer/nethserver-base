==============
Static routes
==============

The panel can used to configure static routes
which don't use the default gateway (for example
to reach private networks connected via dedicated lines).

Remember to add the same network to the :guilabel:`Trusted networks` panel,
if hosts on the remote network should access local services.

Create / Modify
===============

Create a new route to a remote network.

Network address
    Network address for the new route.

Network Mask
    Network mask for the new route.

Router address
    Address of the gateway used to reach the specified network,
    this field is not required.

Description
    A free text field to record any annotation.

After route creation, you can only change
router address and description.
