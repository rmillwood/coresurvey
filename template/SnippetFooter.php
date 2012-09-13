			</div>
		</div>
	</div>
   	<div id="ft" role="contentinfo"></div>
</div>
<!-- add refresh -->
<?php
    /**
     * Displays any Body files required. All Javascript should be run here
     */
     echo $core_page->displayBody();
     echo $core_page->displayJavascript();
?>
<script type="text/javascript">
		// function to show close window
		function ShowClose() {
			$("#snippetclose").fadeIn("slow");
		}
		
	 $(document).ready(function(){
		$("#closewindow").click( function() {
			parent.location.reload();
		});
		
	 });
</script>
</body>
</html>