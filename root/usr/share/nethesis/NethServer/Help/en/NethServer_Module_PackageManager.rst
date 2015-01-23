===============
Software center
===============

Available
=========

The :guilabel:`Available` tab allows selecting modules and optional
packages to install from a list. Pushing the :guilabel:`Add`
button asks for confirmation, before starting the installation
process.

The list can be filtered by category, by pushing the buttons above
it. The special :guilabel:`Everything` category shows the complete
list.

.. NOTE::
   
   1. Both modules and categories are defined by YUM metadata.
   2. Optional packages can be installed also *after* the
      installation of the relative module from the
      :guilabel:`Installed` page.

   
Installed
=========

It lists the modules installed on the system.  Modules are sorted
alphabetically.  On each module the following actions can be performed:

Remove

   The :guilabel:`Remove` button near each installed module removes
   it, after asking confirmation.

Edit

   The :guilabel:`Edit` button near each installed module shows the
   list of optional packages associated with the module.  Pushing the
   :guilabel:`Apply changes` button, checked items are installed, and
   unchecked ones are removed.


Packages
--------

Lists the packages installed on the system, ordered by name. 

Name
    Name of the package.

Version
    Version of the installed package.

Release
    Release number of the installed package and its distribution tag.


Updates
=======

This page lists the available updates for the installed packages.  The
list is updated periodically.

More informations about the updates are shown by clicking on
:guilabel:`Updates CHANGELOG`.

By pushing :guilabel:`Download and install` the listed packages are
updated.


