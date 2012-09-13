       </div>
    </div>
    <div id="ft" role="contentinfo">
        <p class="tright pbad">
            &copy; Core Education and MacMillan Cancer Support <?php echo date('Y'); ?>
        </p>
        <dl class="dpad">
            <dt class="w50 fleft">
                <a href="http://www.core-ed.org.uk">Core Education UK Ltd</a>
            </dt>
            <dd class="w50 fleft">
                    <a href="http://www.macmillan.org.uk">Macmillan Cancer Support</a>
            </dd>
        </dl>
    </div>
</div>
<?php
    /**
     * Displays any Body files required. All Javascript should be run here
     */
     echo $core_page->displayBody();
     echo $core_page->displayJavascript();
?>
</body>
</html>
