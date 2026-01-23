<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
    <body>
        <h1>$Title</h1>
        <meta name="robots" content="noindex, nofollow">
        <ul style="list-style-type: none;">
        <% loop ListOfFieldsForPrinting %>
            <li>
                <strong>$Key:</strong> $Value
            </li>
        <% end_loop %>
        </ul>
        <!-- <script>if (window ==window.top) {window.setTimeout(function(){window.print();}, 500);}</script> -->
    </body>
</html>
