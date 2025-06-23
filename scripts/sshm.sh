#!/usr/bin/env bash
# Created: 20230702 - Updated: 20250407
# Copyright (C) 1995-2025 Mark Constable <markc@renta.net> (AGPL-3.0)
# Exit strategies for triggering Bootstrap5 alerts
# exit 0    - success, no alert and continue
# exit 1-250- error, 'danger' alert and continue
# exit 251  - success, 'success' alert and continue
# exit 252  - info, 'info' alert and continue
# exit 253  - warning, 'warning' alert and continue
# exit 254  - warning, 'warning' alert and empty content
# exit 255  - error, 'danger' alert and empty content

# Strip the first line for alert message in 251/252/253 cases

# Help function to display usage information
help() {
    case $1 in
        c|create)
            echo "Create a new SSH Host file in ~/.ssh/config.d/"
            echo "Usage: sshm create <Name> <Host> [Port] [User] [Skey]"
            ;;
        r|read)
            echo "Show the content values for a host"
            echo "Usage: sshm read <Name>"
            ;;
        u|update)
            echo "Edit the contents of an SSH Host file"
            echo "Usage: sshm update <Name>"
            ;;
        d|delete)
            echo "Delete an SSH Host config file"
            echo "Usage: sshm delete <Name>"
            ;;
        l|list)
            echo "List all host config files"
            echo "Usage: sshm list"
            ;;
        kc|key_create)
            echo "Create a new SSH Key"
            echo "Usage: sshm key_create <Name> [Comment] [Password]"
            ;;
        kr|key_read)
            echo "Show SSH Key"
            echo "Usage: sshm key_read <Name>"
            ;;
        ku|key_update)
            echo "Update SSH Key (alias for key_create)"
            echo "Usage: sshm key_update <Name> [Comment] [Password]"
            ;;
        kd|key_delete)
            echo "Delete SSH Key"
            echo "Usage: sshm key_delete <Name>"
            ;;
        kl|key_list)
            echo "List all SSH Keys"
            echo "Usage: sshm key_list"
            ;;
        i|init)
            echo "Initialize ~/.ssh structure"
            echo "Usage: sshm init"
            ;;
        p|perms)
            echo "Reset permissions for ~/.ssh"
            echo "Usage: sshm perms"
            ;;
        *)
            echo "Usage: sshm <cmd> [args]"
            echo "Commands: create, read, update, delete, list, key_create, key_read, key_update, key_delete, key_list, init, perms, help"
            ;;
    esac
}

# Check if the user has provided a command or needs help
[[ -z $1 || $1 =~ -h ]] && help && exit 1

# Enable debugging if DEBUG is set

# SSH Host management functions
create() {
    local name=$1 host=$2 port=${3:-22} user=${4:-root} skey=${5:-none}

    echo "Host $name
    Hostname $host
    Port $port
    User $user" > ~/.ssh/config.d/$name

    [[ $skey != "none" ]] && echo "  IdentityFile $skey" >> ~/.ssh/config.d/$name || echo "  #IdentityFile none" >> ~/.ssh/config.d/$name
}

read() {
    local name=$1
    [[ -f ~/.ssh/config.d/$name ]] && cat ~/.ssh/config.d/$name | awk '{print $2}' || echo "Notice: ~/.ssh/config.d/'$name' does not exist (254)" && exit 254
}

update() {
    local name=$1
    [[ -f ~/.ssh/config.d/$name ]] && nano -t -x -c ~/.ssh/config.d/$name || echo "Notice: ~/.ssh/config.d/'$name' does not exist (254)" && exit 254
}

delete() {
    local name=$1
    if [[ -f ~/.ssh/config.d/$name ]]; then
        rm ~/.ssh/config.d/$name
        echo "Removed: SSH host '$name' (251)" && exit 251
    else
        echo "Error: SSH host '$name' does not exist (255)" && exit 255
    fi
}

list() {
    for file in ~/.ssh/config.d/*; do
        cat $file | tr '\n' ' ' | awk '{printf "%-15s %25s %5s %10s %20s\n", $2, $4, $6, $8, $10}'
    done
}

# SSH Key management functions
key_create() {
    local name=$1 comment=${2:-"$(hostname)@lan"} password=$3

    if [[ -f ~/.ssh/$name ]]; then
        echo "Warning: SSH Key '~/.ssh/$name' already exists" && exit 254
    fi

    ssh-keygen -o -a 100 -t ed25519 -f ~/.ssh/$name -C "$comment" -N "$password" || echo "Error: SSH key '$name' not created" && exit 254
}

key_read() {
    local name=$1
    [[ -f ~/.ssh/$name.pub ]] && cat ~/.ssh/$name.pub || echo "Warning: '$name' key does not exist (254)" && exit 254
}

key_delete() {
    local name=$1
    [[ -f ~/.ssh/$name ]] && rm ~/.ssh/$name ~/.ssh/$name.pub && echo "Success: removed ~/.ssh/$name and ~/.ssh/$name.pub" || echo "Error: ~/.ssh/$name does not exist" && exit 255
}

key_list() {
    for key in ~/.ssh/*.pub; do
        echo -n "$(basename "$key" .pub) "
        ssh-keygen -lf "$key"
    done
}

# Supplementary functions
copy() {
    local skey=$1 name=$2

    [[ ! -f ~/.ssh/$skey.pub ]] && echo "Error: ~/.ssh/$skey.pub does not exist" && exit 255
    [[ ! -f ~/.ssh/config.d/$name ]] && echo "Error: ~/.ssh/config.d/$name does not exist" && exit 255

    local pubkey=$(cat ~/.ssh/$skey.pub)
    ssh $name "[[ ! -d ~/.ssh ]] && mkdir -p ~/.ssh && chmod 700 ~/.ssh; echo $pubkey >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys;"
    echo "Success: Public key $skey.pub was successfully transferred to $name"
}

perms() {
    find ~/.ssh -type d -exec chmod 700 {} +
    find ~/.ssh -type f -exec chmod 600 {} +
    echo "Updated permissions for ~/.ssh"
}

init() {
    [[ -d ~/.ssh ]] || { mkdir ~/.ssh && chmod 700 ~/.ssh && echo "Created ~/.ssh"; }
    [[ -f ~/.ssh/authorized_keys ]] || { touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && echo "Created ~/.ssh/authorized_keys"; }
    [[ -d ~/.ssh/config.d ]] || { mkdir ~/.ssh/config.d && chmod 700 ~/.ssh/config.d && echo "Created ~/.ssh/config.d"; }

    [[ -f ~/.ssh/config ]] || {
        echo "# Created by sshm on $(date +'%Y%m%d')
Ciphers aes128-ctr,aes192-ctr,aes256-ctr,aes128-gcm@openssh.com,aes256-gcm@openssh.com,chacha20-poly1305@openssh.com

Include ~/.ssh/config.d/*

Host *
  TCPKeepAlive yes
  ServerAliveInterval 30
  ForwardAgent yes
  AddKeysToAgent yes
  IdentitiesOnly yes" > ~/.ssh/config
    }
    perms
}

# Systemctl control functions
start() {
    sudo systemctl start sshd
    sudo systemctl enable sshd
}

stop() {
    sudo systemctl stop sshd
    sudo systemctl disable sshd
}

# Command handler
case $1 in
    c|create) create $2 $3 $4 $5 $6 ;;
    r|read) read $2 ;;
    u|update) update $2 ;;
    d|delete) delete $2 ;;
    l|list) list ;;
    kc|key_create) key_create $2 $3 $4 ;;
    kr|key_read) key_read $2 ;;
    ku|key_update) key_create $2 $3 $4 ;;
    kd|key_delete) key_delete $2 ;;
    kl|key_list) key_list ;;
    i|init) init ;;
    p|perms) perms ;;
    start) start ;;
    stop) stop ;;
    h|help) help $2 ;;
    *) echo "Unknown command '$1'" && help ;;
esac

# Disable debugging if it was enabled

exit 0
