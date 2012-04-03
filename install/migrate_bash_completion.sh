#!/bin/bash
function _migrate () {
    COMPREPLY=()
    local cur="${COMP_WORDS[COMP_CWORD]}"
    local prev="${COMP_WORDS[COMP_CWORD-1]}"

    local opts="--help --status --generate --up --down --init"

    case "${prev}" in
        --init)
            return 0
            ;;
        --status)
            return 0
            ;;
        --up)
            local opts2="--transactional --force --env"
            COMPREPLY=( $(compgen -W "${opts2}" -- ${cur}) )
            return 0
            ;;
        --down)
            local opts2="--transactional --force --env"
            COMPREPLY=( $(compgen -W "${opts2}" -- ${cur}) )
            return 0
            ;;
        --force)
            local opts2="--env"
            COMPREPLY=( $(compgen -W "${opts2}" -- ${cur}) )
            return 0
            ;;
        *)
            ;;
    esac

    COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
    return 0
}

complete -F _migrate -o "default" migrate
