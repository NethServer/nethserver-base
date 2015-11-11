Name: nethserver-base
Summary: NethServer basic configuration
Version: 2.9.3
Release: 1%{?dist}
License: GPL
Source: %{name}-%{version}.tar.gz
BuildArch: noarch
URL: %{url_prefix}/%{name} 

Requires: initscripts
Requires: perl(Locale::gettext)
Requires: perl(Crypt::Cracklib)
Requires: perl(Date::Manip)
Requires: perl(Data::UUID)
Requires: perl(Net::IPv4Addr)
Requires: perl(NetAddr::IP)
Requires: perl-TimeDate
Requires: perl-DateTime-Format-Mail
Requires: perl-Mail-RFC822-Address
Requires: smartmontools
Requires: dbus
Requires: hal
Requires: acpid
Requires: bridge-utils
Requires: vconfig
Requires: mdadm
Requires: sudo
Requires: perl-suidperl
Requires: nc
Requires: iproute
Requires: postfix
Requires: udev >= 147
Requires: yum-plugin-changelog

Requires: nethserver-yum > 1.3.3-2
Requires: nethserver-lib > 2.1.1-1

BuildRequires: nethserver-devtools

%description 
The %{name} package provides the fundamental infrastructure for the
configuration management of NethServer, derived from SME Server event
and template system.

%prep
%setup

%build
%{makedocs}
perl createlinks

# davidep: relocate perl modules under default perl vendorlib directory:
mkdir -p root%{perl_vendorlib}
mv -v lib/perl/{NethServer,esmith} root%{perl_vendorlib}

%install
rm -rf $RPM_BUILD_ROOT
(cd root   ; find . -depth -not -name '*.orig' -print  | cpio -dump $RPM_BUILD_ROOT)
%{genfilelist} $RPM_BUILD_ROOT > %{name}-%{version}-%{release}-filelist

mkdir -p $RPM_BUILD_ROOT/etc/e-smith/events/organization-save

%files -f %{name}-%{version}-%{release}-filelist
%doc COPYING
%defattr(-,root,root)
%dir %attr(755,root,root) /etc/e-smith/events/organization-save
%ghost %attr(600,root,root) /etc/pki/tls/private/NSRV.key
%ghost %attr(644,root,root) /etc/pki/tls/certs/NSRV.crt


%changelog
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


