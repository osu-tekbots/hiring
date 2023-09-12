<?php

function renderBreadcrumb($sectionArray, $pageTitle) {
    if(is_null($sectionArray)) {
        $sectionArray = [];
    }

    $breadcrumbs = " 
       <!-- Breadcrumbs-->
       <ol class='breadcrumb'>";
    
    foreach($sectionArray as $link => $name) {
        $breadcrumbs .= "
        <li class='breadcrumb-item'>
            <a href='$link'>$name</a>
        </li>";
    }

    $breadcrumbs .= "
        <li class='breadcrumb-item active'>$pageTitle</li>
    </ol>";

    echo $breadcrumbs;
}

?>