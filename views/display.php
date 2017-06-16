<?php
if(count($_SESSION['supplierData']) == 1) {
    $supplierData = array_pop($_SESSION['supplierData']);
}
?>
<div class="bannerHeading">
    <h1><?php echo $supplierData['name']; ?></h1>
    <div class="servicesOffered">
        <ul class="list-unstyled list-inline">
            <li>
                <i class="fa fa-wifi"></i>
            </li>
            <li>
                <i class="fa fa-mobile"></i>
            </li>
            <li>
                <i class="fa fa-phone"></i>
            </li>
            <li>
                <i class="fa fa-tv"></i>
            </li>
        </ul>
    </div>
    <h2>Proximus offers deals for Internet, Digital TV, Mobile subscriptions and Fixed line.</h2>
</div>
</div>
</div>
<section class="providerDetail">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="providerContent">
                    <div class="aboutProvider">
                        <h2><?php echo $supplierData['name']; ?></h2>
                        <p><?php echo $supplierData['texts']['description']; ?></p>
                    </div>
                    <div class="productTypes">
                        <h4>Product types</h4>
                        <ul class="list-unstyled">
                            <li>3 packs: internet + digital TV + mobile (4 offers starting from 30€ / month)</li>
                            <li>3 packs: internet + digital TV + mobile (4 offers starting from 30€ / month)</li>
                            <li>3 packs: internet + digital TV + mobile (4 offers starting from 30€ / month)</li>
                            <li>3 packs: internet + digital TV + mobile (4 offers starting from 30€ / month)</li>
                        </ul>
                    </div>
                    <div class="whyProvider">
                        <h4>Why choose Proximus</h4>
                        <ul class="list-unstyled">
                            <li>- Lorem ipsum dolor sit amet, consectetur adipiscing elit. </li>
                            <li>- Nam in nibh vel lorem cursus congue id ullamcorper velit.</li>
                            <li>- Proin consequat ligula et sollicitudin finibus.</li>
                            <li>- Praesent eu arcu lacinia, pharetra lorem eu, egestas lacus.</li>
                        </ul>
                    </div>
                    <div class="compareTips">
                        <h4>Compare tips</h4>
                        <ul class="list-unstyled">
                            <li>- Lorem ipsum dolor sit amet, consectetur adipiscing elit. </li>
                            <li>- Nam in nibh vel lorem cursus congue id ullamcorper velit.</li>
                            <li>- Proin consequat ligula et sollicitudin finibus.</li>
                            <li>- Praesent eu arcu lacinia, pharetra lorem eu, egestas lacus.</li>
                        </ul>
                    </div>
                </div>
            </div>