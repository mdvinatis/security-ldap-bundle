Vinatis Security with LDAP
============

### Installation

````
composer req vinatis/security-ldap-bundle
````

### Config file

Create a config file `vinatis_security_ldab.yaml` in `config/packages` .

### Complete configuration

````
vinatis_security_ldab:

  service:
    dn: 'dc=exemple,dc=com'
    user: 'cn=admin'
    password: 'adminpw'
    host: 'localhost'
    port: 389
    options:
      protocol_version: 3
      referrals: false

  access:
    role: 'ACCESS_APP'

  entity:
    class: My\Entity\User

  legacy:
    cookie_key: 'xxxx'
````

### Add route

````
authentication_token_backoffice:
  path: /authentication_token/backoffice
  methods: ['POST']
````

### Update security.yaml

````
security:

    enable_authenticator_manager: true

    encoders:
        # add legacy encoder
        prestashop_legacy:
            id: Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\Encoder\PrestashopLegacyEncoder
        
        # add entity user encoder
        My\Entity\User:
            algorithm: auto
            migrate_from:
                - prestashop_legacy # uses the "prestashop legacy" encoder configured above

    role_hierarchy:
        # ... add your hierarchy role 

    providers:
        
        # ... add database provider
        doctrine_provider:
            entity:
                class: My\User\Entity\User
                property: email
        
        # ... add LDAP provider
        ldap_provider:
            ldap:
                service: Symfony\Component\Ldap\Ldap
                base_dn: '%env(BASE_DN_LDAP_USER_PROVIDER)%'
                search_dn: '%env(SEARCH_DN_LDAP_USER_PROVIDER)%'
                search_password: '%env(SEARCH_PASSWORD_LDAP_USER_PROVIDER)%'
                uid_key: sAMAccountName
                default_roles: ROLE_USER

    firewalls:
        dev:
            pattern: ^/_(profiler|wdt)
            security: false
        
        # ... api use doctrine_provider
        api:
            pattern: ^/api/
            provider: doctrine_provider
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator

        refresh:
            pattern:  ^/token/refresh
            stateless: true
            #anonymous: true
        
        # ... api use ldap_provider
        backoffice:
            pattern: ^/authentication_token/backoffice
            provider: ldap_provider
            custom_authenticator: Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Authenticator\LdapAuthenticator
            lazy: true
            stateless: true
            user_checker: Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\User\UserChecker
        
        # ... main use doctrine_provider
        main:
            user_checker: Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\User\UserChecker
            json_login:
                provider: doctrine_provider
                check_path: /authentication_token
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

    access_control:
        - { path: ^/authentication_token(/backoffice)?, roles: IS_AUTHENTICATED_ANONYMOUSLY }
````