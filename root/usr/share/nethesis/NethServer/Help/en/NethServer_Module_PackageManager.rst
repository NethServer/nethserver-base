===============
Software center
===============

The main view shows a list of software modules. Checked items represents
installed modules, while unchecked items are the available ones. You can
filter the list by category.

.. NOTE::

    Both modules and categories are defined by YUM metadata.

To install or remove the listed software modules, change the
checkbox states then click the :guilabel:`Apply` button. The next
screen summarizes what is going to be installed and removed. Also, a
list of optional packages is shown, to be selected for
installation.

.. NOTE:: 
    
   Optional components can be installed also *after* the installation
   of the relative module: click the :guilabel:`Apply` button again
   and select them from the summary screen.

   
Installed software
==================

It lists RPM packages installed on the system. Packages are sorted
alphabetically. Displayed fields are:

Name
    Name of the RPM package.

Version
    Version of the installed package.

Release
    Release of the installed package.


Available software updates
==========================

This page lists the available RPM updates. Click the :guilabel:`Download
and install` button to install the listed packages updates.
