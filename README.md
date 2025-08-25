<h1>POINZILLA MAGENTO 2</h1>


<h2> INSTALL MODULE </h2>


<h3> Install from composer </h3>

1) move to your magento root directory
 ```bash
 # cd /var/www/html/path/to/your/magento-root-dir
```

2)  login as the owner of your magento filesystem, for example:
```bash
 # su magentouser
```
3) require and install the package

```bash
 # composer require zoorate/poinzilla
```

4) run comand
```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy [<languages>]
```

<h2> Hyva Theme </h2>

Add the script below to header.phtml as in the stage path app/design/frontend/[Vendor]/[Theme]/Magento_Theme/templates/html

```
<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetch('/customer/section/load/?sections=poinzilla_user')
            .then(res => res.json())
            .then(data => {
                const user = data.poinzilla_user;
                const el = document.querySelector('.poinzilla-login-user-info');
                if (!user || !el) return;
 
                el.setAttribute('data-first-name', user.firstname);
                el.setAttribute('data-last-name', user.lastname);
                el.setAttribute('data-email', user.email);
                el.setAttribute('data-digest', user.digest);
                el.setAttribute('data-consumer-group', JSON.stringify([user.group_id]));
            });
    });
</script>
```

<h2> INFOS AND CONTACTS </h2>

www.poinzilla.com

<h2> LICENSE </h2>

AFL-3.0

