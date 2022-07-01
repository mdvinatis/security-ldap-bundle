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