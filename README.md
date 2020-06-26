# Magento 2 Cache Improvement

The module rewrites the Mview trigger statement function to prevent indexing when only qty is changed on stock item, so FPC can live longer.
 
## Installation  
  
Install the module with composer:  
  
```sh  
composer require tric/magento2-cache-improvement
```  
  
## Support

Tested on Magento 2.3.4 and 2.3.5. Use at your own risk :)