==================
Server certificate
==================

This page shows the currently installed X.509 certificates for TLS/SSL encrypted
communications.

The :guilabel:`Show` button allows to view certificate information and to set a
certificate as default.

The :guilabel:`Upload certificate` button allows to upload certificate files.

The :guilabel:`Request Let's Encrypt certificate` button changes the `Let's
Encrypt`_ configuration and sends out a new certificate request.

The :guilabel:`Edit self-signed certificate` button allows changing the
self-signed certificate, by generating a new one.

.. _`Let's Encrypt`: https://letsencrypt.org/

Request a new Let's Encrypt certificate
=======================================

From this page, you can create a new Let's Encrypt (https://letsencrypt.org/)
valid certificate. This certificate is automatically updated every 30 days.

Notification email
    This email is used by Let's Encrypt for notifications about certificate.
    The server checks itself for certificates validity and warns the ``root``
    user by email if a certificate has expired.

Domains
    A single Let's Encrypt certificate can be valid for multiple domains and
    alias. For instance, mail.nethserver.org, web.nethserver.org... wildcard
    certificates (\*.nethserver.org) aren't supported.

When :guilabel:`REQUEST LET'S ENCRYPT CERTIFICATE` button is clicked, this
server is tested by Let's Encrypt to ensure that you have the right to get a
certificate. The necessary conditions are:

* The server must be reachable from outside at port 80. Make sure your port 80
  is open to the public Internet (you can check with sites like
  http://www.canyouseeme.org/)

* The domains that you want the certificate for, must be public domain names
  associated to server's own public IP. Make sure you have public DNS name
  pointing to your server (you can check with sites like http://viewdns.info/)

