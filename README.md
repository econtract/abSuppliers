# abSuppliers

E-Contract - Suppliers plugin
=============================================

This package offers the integration of the econtract Suppliers API. The suppliers resource provides read only access to the suppliers on Aanbieders.be 




## Installation

Pull this package in through Composer:

```js

    {
        "require": {
            "anb/abSuppliers": "0.*"
        }
    }

```
### Plugin setup

Next, you will need to add several values to your configuration file 
if using bed-rock integration at the parent level of your wordpress then set you keys to  `.env` file:

```
    ANB_API_STAGING = false/true        // API setting for staging/production that is to be used
    ANB_API_KEY     = your_api_key      // API key that will authenticate you in our system.
    ANB_API_SECRET  = your_api_secret   // API secret that will authenticate you in our system.

```
but in case without bed-rock configuration, these use those keys as Global Variables.

```php

    define('ANB_API_STAGING', false/true);        // API setting for staging/production that is to be used
    define('ANB_API_KEY', your_api_key);          // API key that will authenticate you in our system.
    define('ANB_API_SECRET', your_api_secret);    // API secret that will authenticate you in our system.

```


In order to use the API (and thus this package), an API key is required. If you are in need of such a key, please get in touch with Aanbieders.be via [their website](https://www.aanbieders.be/contact).


### Plugin activation
After that just activate plugin from wordpress admin.


## Usage

You can access Suppliers by using short code:

### Supplier Partners

shorcode and configuration

```php
    
    [anb_supplier_partners]

```
supplier partners are configuration deriven, you can drive desired set to data by chaning into configuration

```php

     mark-up          ="div"           // DIV or LI  (not mandatory)
     mark-up-class    ="class-name"    // give your desired class name (not mandatory)
     lang             =nl/fr"          // provide your desired language
     segments         ="sme/consumer"  // segments sme or consumer  (not mandatory)
     products         =""              // internet,mobile,packs,telephony,idtv , for multiple products you can pass it comma seperated (not mandatory)
     image-size       =""              // 200x140 or 100x70 (not mandatory)
     image-color-type = ""             // transparent/grey/colored  (not mandatory)
     mod=""                            // (not mandatory)

```
by default if you haven't pass any segment and product to short it will look for the all possible records against it

totaly cofigured shortcode will be looks like this one :-

```php

   [anb_supplier_partners mark-up="div" mark-up-class="col-sm-2 serviceProvider" lang="nl" segments="sme" products="internet" mod="6"]

```


### Supplier Overview

shorcode and configuration

```php
    
    [anb_suppliers_overview]

```
supplier partners are configuration deriven, you can drive desired set to data by chaning into configuration

```php

     mark-up          = ""            // DIV or LI  // by default it will make a DIV markup (not mandatory)
     mark-up-class    = "class-name"  // give your desired class name (not mandatory)
     lang             = "nl/fr"       // provide your desired language 
     segments         ="sme/consumer" // segments sme or consumer  (not mandatory)
     products         =""             // internet,mobile,packs,telephony,idtv , for multiple products you can pass it comma seperated (not mandatory)
     image-size       =""             // 200x140 or 100x70 (not mandatory)
     image-color-type = ""            // transparent/grey/colored  (not mandatory)

     mod              =""             // (not mandatory)

```
by default if you haven't pass any segment and product to short it will look for the all possible records against it

totaly cofigured shortcode will be looks like this one :-

```php

   [anb_suppliers_overview mark-up="div" mark-up-class="col-sm-2 serviceProvider" lang="nl" segments="sme" products="internet" mod="6"]

```





## License

This package is proprietary software and may not be copied or redistributed without explicit permission.



## Contact

Evert Engelen (owner)

- Email: evert@aanbieders.be


Arslan Hameed (developer)

- Email: arslan.hameed@zeropoint.it
- Telephone: +92 312 517 30 80
