<h1>GA4 Enhanced Ecommerce</h1>

<h2>Installation:</h2>
<strong>Composer:</strong> <br/>

composer require andyworkbase/enhanced-ecommerce --no-update<br/>
composer update andyworkbase/enhanced-ecommerce<br/>

<strong>Manually:</strong> <br/>
1) unpack extension package and upload them into Magento root directory/app/code/
2) php bin/magento setup:upgrade
3) php bin/magento setup:di:compile
4) php bin/magento setup:static-content:deploy

<strong>Configuration</strong> - Stores -> Configuration -> MageCloud -> Enhanced Ecommerce

<h2>Features:</h2>
<ul>
<li>manage events that will be pushed to dataLayer;</li>
<li>manage product identifier;</li>
<li>manage brand attribute;</li>
<li>manage order total value, tax and shipping.</li>
</ul>
