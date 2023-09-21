<?php
    /**
     * Generates a breadcrumb navigation row.
     * 
     * @param array[] $sectionArray An array of associative arrays representing previous pages, 
     *      in the form [link_to_page]=>[name_of_page]
     * @param string $pageTitle The title of the current page
     * 
     * @return void
     */
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