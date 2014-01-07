#
# /etc/profile.d/nethserver.sh -- Custom additions for NethServer environment
#

if [ "$USER" = "root" ]; then
  export PATH=/sbin/e-smith:$PATH
fi

# Feature #1224 -- Date and time in Bash history
export HISTTIMEFORMAT="%Y-%m-%d %T " 

#
# Define shortcut aliases for members of the adm group
#
if groups | grep -s '\badm\b' >/dev/null 2>/dev/null; then

    admpath=/sbin/e-smith

    alias db="sudo ${admpath}/db"
    alias config="sudo ${admpath}/config"
    alias signal-event="sudo ${admpath}/signal-event"
    alias expand-template="sudo ${admpath}/expand-template"

    unset admpath

fi
