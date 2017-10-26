Name: nethserver-base
Summary: NethServer basic configuration
Version: 3.1.1
Release: 1%{?dist}
License: GPL
Source: %{name}-%{version}.tar.gz
BuildArch: noarch
URL: %{url_prefix}/%{name}

Requires: smartmontools
Requires: bridge-utils
Requires: sudo
Requires: nc
Requires: net-tools
# perl-TimeDate is needed for certificate renew
Requires: perl-TimeDate

Requires: yum-plugin-changelog
Requires: nethserver-yum
Requires: nethserver-lib, perl(NethServer::Database::Hostname)

BuildRequires: nethserver-devtools
Requires(post): systemd
Requires(postun): systemd
BuildRequires: systemd

%description
The %{name} package provides the fundamental infrastructure for the
configuration management of NethServer, derived from SME Server event
and template system.

%prep
%setup

%build
%{makedocs}
perl createlinks

mkdir -p root%{perl_vendorlib}
mv -v lib/perl/{NethServer,esmith} root%{perl_vendorlib}
mkdir -p root/%{_nseventsdir}/organization-save
mkdir -p root/%{_nseventsdir}/%{name}-update

for _nsdb in configuration networks routes accounts; do
   mkdir -p root/%{_nsdbconfdir}/${_nsdb}/{migrate,force,defaults}
done 


%install
rm -rf %{buildroot}
(cd root   ; find . -depth -not -name '*.orig' -print  | cpio -dump %{buildroot})
%{genfilelist} %{buildroot} | sed '
\|^%{_sysconfdir}/sudoers.d/| d
\|^%{_sysconfdir}/nethserver/pkginfo.conf| d
' > %{name}-%{version}-%{release}-filelist

%files -f %{name}-%{version}-%{release}-filelist
%defattr(-,root,root)
%doc COPYING
%doc README.rst
%ghost %attr(600,root,root) /etc/pki/tls/private/NSRV.key
%ghost %attr(644,root,root) /etc/pki/tls/certs/NSRV.crt
%ghost %attr(440,root,root) /etc/sudoers.d/10_nethserver
%config %attr(440,root,root) %{_sysconfdir}/sudoers.d/20_nethserver_base
%dir %{_nseventsdir}/%{name}-update
%dir %{_nsdbconfdir}/configuration
%dir %{_nsdbconfdir}/networks
%dir %{_nsdbconfdir}/routes
%dir %{_nsdbconfdir}/accounts
%ghost %attr(0644,root,root) /etc/logviewer.conf
%config(noreplace) %{_sysconfdir}/nethserver/pkginfo.conf

%post
%systemd_post nethserver-system-init.service NetworkManager.service firewalld.service nethserver-config-network.service

%postun
%systemd_postun

%changelog
* Thu Oct 26 2017 Davide Principi <davide.principi@nethesis.it> - 3.1.1-1
- Bogus password strength validator - Bug NethServer/dev#5367

* Tue Oct 10 2017 Davide Principi <davide.principi@nethesis.it> - 3.1.0-1
- Software Center "clear yum cache" fix for Enterprise  - NethServer/dev#5357
- Distro upgrade banner in Software Center page - NethServer/dev#5355

* Fri Sep 08 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.35-1
- CSRF and XSS vulnerabilities in server manager - Bug NethServer/dev#5345

* Fri Jul 21 2017 Davide Principi <davide.principi@nethesis.it> - 3.0.34-1
- DHCP server breaks on new logical interface - Bug NethServer/dev#5331
- Login page l10n is missing - Bug NethServer/dev#5335
- NIC remapping lockout - Bug NethServer/dev#5334

* Wed Jul 12 2017 Davide Principi <davide.principi@nethesis.it> - 3.0.33-1
- AD account provider: web interface doesn't correctly display users with password expiration - Bug NethServer/dev#5318
- Backup config history - NethServer/dev#5314

* Thu Jun 08 2017 Davide Principi <davide.principi@nethesis.it> - 3.0.22-1
- Network Services: pppoe zone - Bug NethServer/dev#5310

* Tue May 30 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.21-1
- Add an example to FQDN validator - NethServer/dev#5297

* Wed May 10 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.20-1
- PKI: self-signed certificate not renewed - Bug NethServer/dev#5278

* Thu Apr 20 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.19-1
- Upgrade from NS 6 via backup and restore - NethServer/dev#5234
- Ipaddr prop missing from green+DHCP interface - Bug NethServer/dev#5272

* Mon Mar 06 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.18-1
- Migration from sme8 - NethServer/dev#5196

* Mon Jan 30 2017 Davide Principi <davide.principi@nethesis.it> - 3.0.17-1
- Domain admins members are not granted full server-manager access - Bug NethServer/dev#5209

* Thu Jan 26 2017 Davide Principi <davide.principi@nethesis.it> - 3.0.16-1
- Fix service boot order -- NethServer/nethserver-base#81
- Remove legacy call to network-create event -- NethServer/nethserver-base#78
- Fix slow disk space calculations -- NethServer/nethserver-base d9a04df

* Thu Jan 19 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.15-1
- Error on changing user's password when login with short-hand user format - NethServer/dev#5203

* Mon Jan 16 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.14-1
- DC: restore configuration fails - Bug NethServer/dev#5188
- Exhibit bad network configuration - NethServer/dev#5193

* Wed Jan 11 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.13-1
- Logical interfaces UI tweaks - NethServer/dev#5189
- Traffic shaping for logical interfaces  - NethServer/dev#5187

* Thu Dec 15 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.12-1
- Default "admins" config DB record - NethServer/dev#5157
- Invoke certificate-update event when a valid certificate is renewed - NethServer/dev#5174

* Tue Oct 18 2016 Davide Principi <davide.principi@nethesis.it> - 3.0.11-1
- Let's Encrypt: missing chain file - Bug NethServer/dev#5134

* Mon Oct 17 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.10-1
- Clear-text password in /var/log/secure - Bug NethServer/dev#5130
- Network page: missing mac address for bridged interfaces -  NethServer/dev#5123

* Wed Sep 28 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.9-1
- Enchance traffic shaping - NethServer/dev#5113

* Fri Sep 23 2016 Davide Principi <davide.principi@nethesis.it> - 3.0.8-1
- Unhandled USB ethernet plug events - Bug NethServer/dev#5109
- Nsdc domain join fails with long hostname - Bug NethServer/dev#5110

* Thu Sep 01 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.7-1
- Default centos-release-scl  package - NethServer/dev#5089
- Let's Encrypt: generated certificate is invalid - Bug NethServer/dev#5092

* Fri Aug 26 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.6-1
- Fix log error message from admin-todos - NethServer/dev#5078

* Thu Aug 25 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.5-1
- Advanced static routes - NethServer/dev#5079
- systemd: Failed to create mount unit file: home.mount - Bug NethServer/dev#5086
- Improve DHCP on green - NethServer/dev#5078

* Mon Aug 01 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.4-1
- Accounts: web interface tweaks - NethServer/dev#5073

* Thu Jul 21 2016 Davide Principi <davide.principi@nethesis.it> - 3.0.3-1
- Dashboard: display Gateway field inside Interfaces tab - NethServer/dev#5056
- Web UI: missing labels - Bug NethServer/dev#5061
- Self-signed certificate doesn't save changes - Bug NethServer/dev#5059
- Move "DNS servers" inline documentation to Network page. - Bug NethServer/dev#5060

* Tue Jul 12 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.2-1
- Fixes blank checkbox on "Network services" page #5045

* Tue Jul 12 2016 Davide Principi <davide.principi@nethesis.it> - 3.0.1-1
- Prop "user" in proxy settings isn't saved -- NethServer/dev#5043

* Thu Jul 07 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 3.0.0-1
- First NS7 release

* Mon Jun 27 2016 Davide Principi <davide.principi@nethesis.it> - 2.11.1-1
- Software Center fails to install packages from NethForge - Bug #3408 [NethServer]

* Tue Jun 14 2016 Davide Principi <davide.principi@nethesis.it> - 2.11.0-1
- Network IP alias on bridge interfaces - Feature #3406 [NethServer]

* Mon Jun 06 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.10.4-1
- Fully disable SELinux - Enhancement #3400
- Enable NethForge, if present

* Thu May 26 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.10.3-1
- User and password with blank spaces in PPPoE configuration - Bug #3385 [NethServer]

* Wed Apr 27 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.10.2-1
- Network configuration: alias IP overwritten - Bug #3381

* Tue Apr 05 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.10.1-1
- Bond miimon default value - Enhancement #3373 [NethServer]

* Thu Mar 31 2016 Davide Principi <davide.principi@nethesis.it> - 2.10.0-1
- Default bonding mode to active-backup - Enhancement #3299 [NethServer]

* Thu Mar 03 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.9.7-1
- Add a yum clean all button if update fails - Feature #3360 [NethServer]
- Samba core files are listed by log viewer - Bug #3334 [NethServer]

* Fri Feb 26 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.9.6-1
- Role disappears from ethernet interface - Bug #3357 [NethServer]
- Let's Encrypt (partial) support  - Feature #3355 [NethServer]

* Mon Nov 30 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.9.5-1
- The description of trusted network can not be modified - Bug #3321 [NethServer]
- bond confuses network configuration - Bug #3306 [NethServer]

* Mon Nov 23 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.9.4-1
- Dashboard: avoid blocking on todos ajax calls - Enhancement #3322 [NethServer]

* Wed Nov 11 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.9.3-1
- MultiWAN: remove static routes for checkip - Enhancement #3289 [NethServer]
- DB key name clash in networks db - Bug #3272 [NethServer]

* Mon Oct 12 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.9.2-1
- error in parsing ifcfg-eth0 when installing ns6.7rc1 on a centos 6.7 minimal - Bug #3282 [NethServer]

* Tue Sep 29 2015 Davide Principi <davide.principi@nethesis.it> - 2.9.1-1
- Translation: fix Welcome_body string. Enhancement #3265 [NethServer]

* Thu Sep 24 2015 Davide Principi <davide.principi@nethesis.it> - 2.9.0-1
- Drop lokkit support, always use shorewall - Enhancement #3258 [NethServer]

* Thu Aug 27 2015 Davide Principi <davide.principi@nethesis.it> - 2.8.1-1
- server-manager PPPoE support - Enhancement #3227 [NethServer]

* Fri Jul 17 2015 Davide Principi <davide.principi@nethesis.it> - 2.8.0-1
- PPPoE support - Feature #3218 [NethServer]

* Wed Jul 15 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.7.4-1
- Event trusted-networks-modify - Enhancement #3195 [NethServer]

* Wed Jul 08 2015 Davide Principi <davide.principi@nethesis.it> - 2.7.3-1
- Fix bug #3216 [NethServer]

* Mon Jun 22 2015 Davide Principi <davide.principi@nethesis.it> - 2.7.2-1
- Wrong Server Manager menu category order  - Bug #3197 [NethServer]
- Log viewer close goes back to last module instead of back to log viewer - Bug #3138 [NethServer]

* Wed May 20 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.7.1-1
- Alias ifcfg-ethX:Y files invalid syntax - Bug #3091 [NethServer]
- Trusted network validator - Bug #3025 [NethServer]
- Trusted "Network address" should be a valid Network prefix  - Bug #3010 [NethServer]
- Localize "password expire" notifications - Enhancement #2887 [NethServer]
- Base: display green network inside "Trusted network" page - Enhancement #2711 [NethServer]

* Thu Apr 23 2015 Davide Principi <davide.principi@nethesis.it> - 2.7.0-1
- Language packs support - Feature #3115 [NethServer]
- Dashboard: display IP of red interfaces configured with DHCP - Enhancement #3096 [NethServer]
- Task running forever - Bug #3078 [NethServer]

* Thu Apr 02 2015 Davide Principi <davide.principi@nethesis.it> - 2.6.5-1
- Web proxy: exclude local sites when mode is transparent - Enhancement #3099 [NethServer]
- Dashboard: remove/hide uneeded services from the list - Enhancement #2795 [NethServer]
- Base: service description - Enhancement #2765 [NethServer]

* Thu Mar 26 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.6.4-1
- Firewall-base: multi-wan dhcp failover not supported - Enhancement #2827 [NethServer]

* Thu Mar 12 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.6.3-1
- Progress bar stuck in drpm download - Bug #3079 [NethServer]

* Wed Mar 11 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.6.2-1
- Unexpected ethernet interface rename - Bug #3082 [NethServer]

* Thu Mar 05 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.6.1-1
- Network page unexpected roles - Bug #3076 [NethServer]
- Dashboard Interfaces widget and VLAN - Bug #3075 [NethServer]

* Tue Mar 03 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.6.0-1
- Move Logout button at top right - Enhancement #3046 [NethServer]
- Restore from backup, disaster recovery and network interfaces - Feature #3041 [NethServer]
- Differentiate root and admin users - Feature #3026 [NethServer]
- Add includedir directive to /etc/sudoers - Enhancement #3012 [NethServer]
- Display Dashbord at top of menu - Enhancement #3011 [NethServer]
- Shorewall: allow template-custom for ESTABLISHED and RELATED connection inside rules file - Enhancement #2999 [NethServer]
- Show default password on server-manager login - Enhancement #2998 [NethServer]
- Raid critical status on dashboard does not show failed partitions - Bug #2995 [NethServer]
- Refactor Organization contacts page - Feature #2969 [NethServer]
- Package Manager: new UPDATE button and optional packages selection - Feature #2963 [NethServer]
- squidGuard: support multiple profiles - Enhancement #2958 [NethServer]
- Base: first configuration wizard - Feature #2957 [NethServer]

* Wed Jan 14 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.5.5-1.ns6
- Raid critical status on dashboard does not show failed partitions - Bug #2995 [NethServer]
- Correctly handle history back button - Enhancement #2958 [NethServer]

* Tue Dec 09 2014 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.5.4-1.ns6
- First-boot: execute system-init event before tty - Enhancement #2952 [NethServer]
- Modify all users if the ldap organisation contacts is updated - Bug #2931 [NethServer]
- Notify user if event fails - Enhancement #2927 [NethServer]
- DNS: remove role property from dns db key - Enhancement #2915 [NethServer]
- Network config during install is ignored - Bug #2796 [NethServer]
- Drop TCP wrappers hosts.allow hosts.deny templates - Enhancement #2785 [NethServer]

* Wed Nov 19 2014 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.5.3-1.ns6
- First-boot: execute system-init event before tty - Enhancement #2952 [NethServer]
- Notify user if event fails - Enhancement #2927 [NethServer]

* Thu Oct 23 2014 Davide Principi <davide.principi@nethesis.it> - 2.5.2-1.ns6
- Lokkit: missing port range support for network services - Bug #2923 [NethServer]
- Dnsmasq: daemon doesn't start if NameServers property contains more than 2 addresses - Bug #2918 [NethServer]

* Fri Oct 17 2014 Davide Principi <davide.principi@nethesis.it> - 2.5.1-1.ns6
- Red interface: gateway setting wiped out - Bug #2920 [NethServer]

* Wed Oct 15 2014 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 2.5.0-1.ns6
- Access to server-manager from multiple green networks - Bug #2896
- Network services: refactor web interface - Enhancement #2895
- Dashboard: running status of service is not displayed - Bug #2875
- Support DHCP on multiple interfaces - Feature #2849
- NetworkAdapter: adaptive UI fields - Enhancement #2807
- Backup config: list only relevant files - Feature #2739

* Thu Oct 02 2014 Davide Principi <davide.principi@nethesis.it> - 2.4.0-1.ns6
- Notification "${0}%" string flashes on login screen - Bug #2876 [NethServer]
- Handle nethserver-firewall-base uninstallation - Enhancement #2873 [NethServer]
- System initialization: change default ip addresses - Enhancement #2872 [NethServer]
- Reconfigure networking on NIC replacement - Feature #2837 [NethServer]
- Cannot access Server Manager after migration - Bug #2786 [NethServer]
- Remove obsolete console and bootstrap-console commands - Enhancement #2734 [NethServer]
- Web UI: advanced network configuration  - Feature #2719 [NethServer]

* Wed Aug 20 2014 Davide Principi <davide.principi@nethesis.it> - 2.3.0-1.ns6
- Embed Nethgui 1.6.0 into httpd-admin RPM - Enhancement #2820 [NethServer]
- Base: remove rsyslog.conf template - Bug #2772 [NethServer]
- Firewall: allow and deny access to local services - Enhancement #2752 [NethServer]
- Certificate migration fails if "key" prop is missing - Bug #2745 [NethServer]
- Base: split 'local networks' into 'static routes' and 'trusted networks' - Enhancement #2743 [NethServer]
- Remove obsolete console and bootstrap-console commands - Enhancement #2734 [NethServer]
- Web UI: advanced network configuration  - Feature #2719 [NethServer]
- Custom firewall rules - Feature #2716 [NethServer]
- Firewall: select default policy - Feature #2714 [NethServer]
- Dashboard: uptime is incomplete - Bug #2708 [NethServer]
- Firewall: support custom objects - Feature #2705 [NethServer]
- Firewall-base: add support for multi-wan - Feature #2332 [NethServer]

* Mon Mar 24 2014 Davide Principi <davide.principi@nethesis.it> - 2.2.1-1.ns6
- Built again to fix online documentation - #2700 [NethServer]

* Mon Mar 24 2014 Davide Principi <davide.principi@nethesis.it> - 2.2.0-1.ns6
- YUM categories in PackageManager - Feature #2694 [NethServer]
- Remove absolute URLs from PackageManager - Enhancement #2692 [NethServer]

* Mon Mar 10 2014 Davide Principi <davide.principi@nethesis.it> - 2.1.2-1.ns6
- User can't set his own UserProfile fields - Bug #2684 [NethServer]
- Backup Notification to System administrator fails by default - Bug #2675 [NethServer]

* Wed Feb 26 2014 Davide Principi <davide.principi@nethesis.it> - 2.1.1-1.ns6
- Fix for default module for non-admin users - Bug #2630 [NethServer]

* Wed Feb 26 2014 Davide Principi <davide.principi@nethesis.it> - 2.1.0-1.ns6
- Rebranding fails when new kernel is installed - Bug #2664 [NethServer]
- Installer improvements - Enhancement #2660 [NethServer]
- Revamp web UI style - Enhancement #2656 [NethServer]
- Default module for non-admin users - Bug #2630 [NethServer]
- Dashboard: infinite loop on XHR failure - Bug #2628 [NethServer]
- Implement hostname-modify event for samba  - Enhancement #2626 [NethServer]

* Wed Feb 12 2014 Davide Principi <davide.principi@nethesis.it> - 2.0.1-1.ns6
- Customizable X509 email field - Enhancement #2650 [NethServer]

* Wed Feb 05 2014 Davide Principi <davide.principi@nethesis.it> - 2.0.0-1.ns6
- Start messagebus service - Enhancement #2645 [NethServer]
- No feedback from Shutdown UI module - Bug #2629 [NethServer]
- NethCamp 2014 - Task #2618 [NethServer]
- Remove bootstrap-console - Enhancement #2582 [NethServer]
- Move admin user in LDAP DB - Feature #2492 [NethServer]
- Dashboard: show raid status - Enhancement #2490 [NethServer]
- Update all inline help documentation - Task #1780 [NethServer]
- ISO: interactive installer - Feature #1757 [NethServer]
- Base: avoid double network restart on first boot event - Enhancement #1742 [NethServer]
- Dashboard: new widgets - Enhancement #1671 [NethServer]

* Wed Dec 18 2013 Davide Principi <davide.principi@nethesis.it> - 1.5.0-1.ns6
- Remove external JS libraries from source repositories - Enhancement #2167 [NethServer]
- Always deliver mail to local admin user - Enhancement #2102 [NethServer]
- Directory: backup service accounts passwords  - Enhancement #2063 [NethServer]
- Process tracking and notifications - Feature #2029 [NethServer]
- Service supervision with Upstart - Feature #2014 [NethServer]

* Thu Oct 17 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.3-1.ns6d
- Add AdminIsNotRoot to keep admin and root password in sync #2277
- Store root server certificate in NSS database #2248
- Avoid resolv.conf overwrite by dhclient #2213
- Fix lokkit configuration #2205
- Add language code to URLs #2113 [Nethgui]
- Implement configurable log rotation and retention policies #2053

* Wed Aug 28 2013 Davide Principi <davide.principi@nethesis.it> - 1.4.1-1.ns6
- Import nethserver-manager code from nethserver-base - Enhancement #2110 [NethServer]
- Base: validate configuration from NetworkAdapter UI module - Enhancement #2103 [NethServer]
- Update NetworksDB on udev events - Enhancement #2075 [NethServer]

* Thu Jul 25 2013 Davide Principi <davide.principi@nethesis.it> - 1.4.0-1.ns6
- Base: bootstrap-console type default is "configuration" - Enhancement #2079 [NethServer]
- Lib: synchronize service status prop and running state - Feature #2078 [NethServer]
- Base: new PackageManager UI module - Feature #1767 [NethServer]
- Base: select multiple items in package-manager - Enhancement #1748 [NethServer]

* Wed Jul 17 2013 Davide Principi <davide.principi@nethesis.it> - 1.3.1-1.ns6
- Lib: synchronize service status prop and chkconfig - Feature #2067 [NethServer]

* Mon Jul 15 2013 Davide Principi <davide.principi@nethesis.it> - 1.3.0-1.ns6
- Imported PackageManager UI module - Feature #1767 [NethServer]
- Select multiple items in package-manager - Enhancement #1748 [NethServer]

* Mon Jul 15 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.2.5-1.ns6
- Fix static route generations #2057

* Fri Jul 12 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.2.4-1.ns6
- Backup: implement and document full restore #2043

* Wed May 29 2013 Davide Principi <davide.principi@nethesis.it> - 1.2.3-1.ns6
- NethServer\Tool\PasswordStash: added setAutoUnlink() method #1746

* Tue May  7 2013 Davide Principi <davide.principi@nethesis.it> - 1.2.2-1.ns6
- system-adjust action: import symbols from nethserver yum plugin  #1870

* Thu May  2 2013 Davide Principi <davide.principi@nethesis.it> - 1.2.1-1.ns6
- Fixed assignment of green role when one NIC only is installed #1883

* Tue Apr 30 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.2.0-1.ns6
- Rebuild for automatic package handling. #1870
- Refactor firewall configuration #1875
- Add logviewer module #470
- Handle static routes #1886
- Add migration code #1794
- Various fixes: #1754 #1878

* Tue Mar 19 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.1.2-1.ns6
- first-boot: postpone init-repo execution to avoid clean up of event-queue

* Tue Mar 19 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.1.1-1.ns6
- New dashboard #583
- first-boot event: execute network restart (#1741) do not block on error
- Add migration code #1702
- Add support for filesystems options #1658
- Various bugfixes

* Thu Jan 31 2013 Davide Principi <davide.principi@nethesis.it> - 1.1.0-1.ns6
- Removed "pv" requirement
- Added Certificate Management #1503
- SME bash completion #1619
- admin's mailbox #1635
- Fix #1628


