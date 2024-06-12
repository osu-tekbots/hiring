        <?php 
        /**
         * Placeholder for future implementation
         */
        ?>
    </main>
    <footer>
        <?php
            if(!in_array($_SERVER['SCRIPT_NAME'], [
                '/education/hiring/pages/index.php', 
                // '/education/hiring/pages/login.php', 
                // '/education/hiring/pages/local/login.php',
                // '/education/hiring/pages/local/forgotPassword.php',
                // '/education/hiring/pages/local/newUser.php',
                // '/education/hiring/pages/error.php'
            ]))
                
                /* ?entry.1671399415=SPT specifies checkbox to select for question 1 */
                echo '
                    <a href="https://docs.google.com/forms/d/e/1FAIpQLSdK6-dYdAUel_5yGWeJWiO7ptoXFscGZzRHhRI4FY7I1BDRog/viewform?entry.1671399415=SPT" target="_blank" class="btn" type="button" style="position: fixed; right: 20px; bottom: 20px; background: rgb(195, 69, 0); color: #fff; font-size: 1rem; padding: .375rem .75rem; border-radius: 0.25rem; font-weight: normal;">
                        <i class="far fa-comment mr-1"></i>
                        Feedback
                    </a>
                '; 
        ?>
    </footer>
</body>
</html>