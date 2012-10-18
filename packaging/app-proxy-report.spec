
Name: app-proxy-report
Epoch: 1
Version: 1.4.0
Release: 1%{dist}
Summary: Filter and Proxy Report
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
The Filter and Proxy Report provides a view of web usage on your network.

%package core
Summary: Filter and Proxy Report - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-reports-core
Requires: app-reports-database-core
Requires: app-tasks-core

%description core
The Filter and Proxy Report provides a view of web usage on your network.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/proxy_report
cp -r * %{buildroot}/usr/clearos/apps/proxy_report/

install -D -m 0644 packaging/app-proxy-report.cron %{buildroot}/etc/cron.d/app-proxy-report
install -D -m 0755 packaging/proxy2db %{buildroot}/usr/sbin/proxy2db

%post
logger -p local6.notice -t installer 'app-proxy-report - installing'

%post core
logger -p local6.notice -t installer 'app-proxy-report-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/proxy_report/deploy/install ] && /usr/clearos/apps/proxy_report/deploy/install
fi

[ -x /usr/clearos/apps/proxy_report/deploy/upgrade ] && /usr/clearos/apps/proxy_report/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-proxy-report - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-proxy-report-core - uninstalling'
    [ -x /usr/clearos/apps/proxy_report/deploy/uninstall ] && /usr/clearos/apps/proxy_report/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/proxy_report/controllers
/usr/clearos/apps/proxy_report/htdocs
/usr/clearos/apps/proxy_report/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/proxy_report/packaging
%exclude /usr/clearos/apps/proxy_report/tests
%dir /usr/clearos/apps/proxy_report
/usr/clearos/apps/proxy_report/deploy
/usr/clearos/apps/proxy_report/language
/usr/clearos/apps/proxy_report/libraries
/etc/cron.d/app-proxy-report
/usr/sbin/proxy2db
