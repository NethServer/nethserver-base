==================
Server certificate
==================

This page shows the currently installed SSL certificates.

:guilabel:`Show` button allows to view certificate informations and to set a certificate as default.

The :guilabel:`New Letsencrypt certificate` button allows creating automatically a new [Let's Encrypt](https://letsencrypt.org/ "Let's Encrypt Home page") valid certificate

The :guilabel:`New CA certificate` button allows creating a new self-signed certificate.

The :guilabel:`New certificate upload` button allows to upload certificate files.

Create a Letsencrypt certificate
================================

From this page, you can create a new Let's Encrypt (https://letsencrypt.org/) valid certificate. This certificate is automatically updated every 30 days.

Notification email
    This email is used by Let's Encrypt for notifications about certificate

Domains
    A single Let's Encrypt certificate can be valid for multiple domains and alias. For instance, mail.nethserver.org, web.nethserver.org...
    wildcard certificates (\*.nethserver.org) aren't supported

When :guilabel:`NEW LETSENCRYPT CERTIFICATE` button is clicked, this server is tested by Let's Encrypt to ensure that you have the right to get a certificate. The necessary conditions are:
* The server must be reachable from outside at port 80. Make sure your port 80 is open to the public Internet (you can check with sites like http://www.canyouseeme.org/)
* The domains that you want the certificate for, must be public domain names associated to server own public IP. Make sure you have public DNS name pointing to your server (you can check with sites like http://viewdns.info/)
