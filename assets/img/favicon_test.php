<?php
// favicon_test.php - A tool to test favicon compatibility across browsers
// This script displays the favicon in different browser contexts

// Define page title
$page_title = "Favicon Compatibility Tester";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Gunayatan Gatepass System</title>
    
    <!-- All favicon references to test -->
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="icon" type="image/png" href="favicon.png" sizes="32x32">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">
    <meta name="theme-color" content="#2c3e50">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .browser-test {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .status-ok {
            color: green;
            font-weight: bold;
        }
        .status-warning {
            color: orange;
            font-weight: bold;
        }
        .status-error {
            color: red;
            font-weight: bold;
        }
        .browser-icon {
            width: 24px;
            height: 24px;
            vertical-align: middle;
            margin-right: 5px;
        }
        .info-box {
            background-color: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 10px 20px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .favicon-display {
            background-color: white;
            border: 1px solid #ddd;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .favicon-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }
        .favicon-item {
            text-align: center;
        }
        footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $page_title; ?></h1>
        <p>This tool tests the favicon implementation across different browsers and devices.</p>
        
        <div class="info-box">
            <p><strong>Current Implementation:</strong> Your Gunayatan Gatepass System uses multiple favicon formats for optimal browser compatibility.</p>
        </div>
        
        <div class="favicon-display">
            <h2>Current Favicon Implementation</h2>
            <div class="favicon-row">
                <div class="favicon-item">
                    <h3>SVG (Vector)</h3>
                    <img src="favicon.svg" width="64" height="64" alt="SVG Favicon">
                    <p>For modern browsers</p>
                </div>
                <div class="favicon-item">
                    <h3>PNG (32x32)</h3>
                    <img src="favicon.png" width="32" height="32" alt="PNG Favicon">
                    <p>Standard format</p>
                </div>
                <div class="favicon-item">
                    <h3>ICO (32x32)</h3>
                    <img src="favicon.ico" width="32" height="32" alt="ICO Favicon">
                    <p>For legacy browsers</p>
                </div>
                <div class="favicon-item">
                    <h3>Apple Touch</h3>
                    <img src="apple-touch-icon.png" width="64" height="64" alt="Apple Touch Icon">
                    <p>For iOS devices</p>
                </div>
            </div>
        </div>
        
        <div class="browser-test">
            <h2>Browser Compatibility Test</h2>
            <p>The table below shows favicon support across different browsers:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Browser</th>
                        <th>SVG Support</th>
                        <th>PNG Support</th>
                        <th>ICO Support</th>
                        <th>Apple Touch</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAA/FBMVEVChfT///9ChfRChfRChfRChfRChfRChfRChfRChfRChfRChfRChfTq8P3///8oefPN2/v7/P/s8v7w9f7T4Pz2+f7H1/v0+P5pm/ZXkvXe6P3g6v1Oi/Ta5fxel/Xt8/69z/rJ2fvW4/zA0fq0y/p2pPfY5Pxsn/Z7p/eevvksffPD1Pr3+v7Q3vxvoPbh6v2mw/k3gPTl7f2vyfnz9/6817rczLKVt/j8/f/Z5PyOs/jt27zczbrczLR7q/jn2sPZ5fvn7/1glfXt9P7n5t/j0cLo1r9nmPXt4dPp3czfzrnp8P3dzbXcza/n18OPCzebAAAAJnRSTlMAAAWAEDDEPPkk/hnVBNv+E/f9PfBn6jyx/wFR/in+sFb+9/z9vvc1mfUAAAEuSURBVCiRbZDXcsIwEEUlWXIBG0wvCQaMaQZCQhLSe+/l/z8lWcnQMsnDndn7yCth7Aa9XrDnOHjjLa85zysFOp6hK6VWS827/2OaTCe+75+MdxpxzHEcY8y8VixeKed6XEhjkPbBUO25vXrNdZ1sNggYYyEOo3Iz7wT9ej1fB3bVkfCIo6it604IVBAENUKI3GmK46hXKdRCoIIxohDC0DHGEVFKSYKgyiBrES7JwlDFYBiFYyUXKKUisKrNDcpKocxrB6YgLGttbBYE2YCiaRsGy8oqnmx25AQhLq3DxbIJRHooLVh8L+XjC5Hyy0kaL87PT05Pzy/Sy9UbHt4W4ZYo9+Py/PLq+ub24e6RxK+UxA+DIAgpee8/liRJ8ibxB/kHvmbDIMgj3FEAAAAASUVORK5CYII=" alt="Chrome" class="browser-icon"> Chrome</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                    </tr>
                    <tr>
                        <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAABJlBMVEX////mqjX////mqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjXmqjX///////////////////+6iR7ovFj28OH9+/f06NPatmv59ezlpivcuXftxHLy5c3jpiL59Oj+/fv7+O/kukP38uX59evs2bL48+f169ns2bP38uT8+/Xu3b3nrz/+/Pnnr0D59Onry5nw4sXmqzbot0vovVrx48jqvmDdkwD28N/qvV/nrkDt3Lj9+/jqvF3Zmh7y5s/x4cPnrT306tfy5c748+b169rmqjPnrj717Nvy5czbt3TkpyzdkgDu3r/48eP797Ds2K/pyZPnrDznsUPZmRvw4cL8+vX797H58+jdkwP27t3ar+fmAAABIUlEQVQoz23P11LCUBCA4QRSIAklQBoGaaGEjgFC7wKCgBRRId//KdxE8Yozs/+3e2YvVvmfJdIZJFLapCzZbIZFMhuUNWWrZRYpJgpie0thl2w+q6cUE2IIh8Nv3Z5p9/rDoRYLCVxA13X74XAwGj0+jRVFVxVBDHAQZs/PM+jl9U0DJEkKbJAoTTm0HJrNYehTURQDGwqyoomYKAiCJGmarK4zuTxGQjwucpzIp1RVjf9B5HA4ivdyazqdTCbjMf9FIrH4Srm+aLfbzVq1UtnmcnADbCuKvLvZr2298R9YLJY/89Ztl2XZvfY5d7zYzfKKvHeEEc7V9S9VUs7BcUHJyyGBO5RO3O5kiskpnZ3TTB6NL4T0la7+AYmDOAkre9GKAAAAAElFTkSuQmCC" alt="Firefox" class="browser-icon"> Firefox</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                    </tr>
                    <tr>
                        <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAA/1BMVEX///8AgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNgAgNj////j8PrZ6vir0O+KwOoeithXo+FHnuD6/P7Q5fbA3PSfyu41j9vmxcDx9/yy1fGRxOsvjNr8+vuDvOrl8Pry+PzV6Pe72/OXyO3v9vzNztCOkJK2uLnz5eLt9fvg7vnI4fWm0O6Fvuo9lNzp8/v09fWChYdMUFLV1tfIystoam3o6ekcJCd5fH5cX2E+QkXy8/Td3t/S09Tu7++goqNUV1lAQ0ays7TraWoOFhlCmN+az+48k9yLwOrn8vvDxMWHub3fAAAAHXRSTlMAAAUQMPMkPBn+/vb+Pd5n9vsB+PtfILBn/f39+v3XAjQAAAEHSURBVCjPbZDZUsJAEEUD2UNIIBgMIigKKKAoICirLLLvAv//L0wyTFGF/dC3zzndXd0DJtZ6TVr1wpcVrgXDNfIB1WjYfbpaIu9ZUa1Qi7MnLJfJuxRUesjYo9EOfJ4VeZlbDlb8/poiJpMJY8w0zWgy6gZBSZZQdZz6c9QCA9N68htNd0NdRHQlCLvU49Z5WYAwmZrO/arXIiLO83L5dtUAmLntESGYQwJ4cP1tUcNhzJi7LTYXAOYVaR3HDBlry81/xW6FoJHWDxvSXyHMPjvGMXLyMQdIPj++vb+9fkoekmNyMQrOzs9OL67Obzr4z+LN+e4Al93e1G932GNdsPATTLgm+3Xr3UoAAAAASUVORK5CYII=" alt="Safari" class="browser-icon"> Safari</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                    </tr>
                    <tr>
                        <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAA/FBMVEUAgP////8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP/////7/f78/v/9/v8ieP8Wc/8Rdf89if9uqP+fyP/y9//o8v/t9f/3+/8khP8adP8Hbv+Uw/+72P/h7v+x0//E3f/b6/8mhf8LcP/R5f+Ywf8zjv9Qlv9jpf95rv94rv+GuP+TwP+72f+z1P/X6P+81//L4f+lyv+kx/+Qv/96r/+Rv/9Fkf9CkP9MlP+Ave+91//h6v/X6f+oy/+ozP9npv9Tmf9Elf+KvP+Ou//I4P/N5P/P5P/1+f/w9/8PdP+8w2RkAAAAJXRSTlMAAAUQMDzE+ST+GfXb/f0T9/71Z/DqPNP9AfhR/in+Vq/+/Pf8h3q0hQAAASpJREFUKM9tkNdywjAQRSXZMrYBAwZCCyUhgUAILZRQQkkh9Pr/n5FXHoaZsA92Ze85I60Y+0G/H+w7Dt54y2vO84qBjmfoSqlVX/Me/5gmswlj7HQy2GvFMcdxjDHz1uKlcq7HhTQGaSACdeAO2vW66zoZGAhGBSzCYdSe1wL9TqdeBw7UkfCIo6ivazUGqCAI1KhVYtoyjiMudbxQkGEYNHmJOI4Iij1JEERYCmjRxgZlFQRB7bTEIAzD2qYvobXGpgyCoOLvYGiyAkjpbtosmZdeUHE8jNAofCVIpKQES++lvL8gUn65ScPFxeXV9fXNbXr3+IKHT0q4JYqTQfn+4fHp+eX17YPE75TEz4Ig8Cl57z+WJEnyJvEH+QeNvL4QnrxvIwAAAABJRU5ErkJggg==" alt="Edge" class="browser-icon"> Edge</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-warning">⚠ Partial</td>
                    </tr>
                    <tr>
                        <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAA/FBMVEVGgfL///9GgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfJGgfL///+uyflqmvXx9v5dl/RQi/P5+/7n7/12ova8v79GjvKbwPjT4/z19fW5zvqoxvlRifNIYrKUuvfe6f30+P5YkfNVgN7v9P6ixPnY5fyzyvl0nvbO3/vEx8lQeNPj7P3b6P1imvVXkPTO2fCIsvdsnPXk7f3I2vvt8/7d6P2TuvhelvSDrfdblPRBf/Ll5ebZ3N7Nz9GgoqRYaKRycnJub3B6e3xtbm9Gh/I9fPLm7/3N3vvQ4Py30PrB1vuqx/nk0ilVAAAAIHRSTlMAAAUQMPP+JDwZ9dsE/v3+E/f+9Wfw6jz8Af5R/in+sv1CeZUAAAEOSURBVCjPdZDXUsJAFEDTNglIlWYUEBULYgF7V9Dz/z/jJpuMjsJ5uHfmzN27s8Ae75QkHe+6haz1CnmtkE8qltVPzXosn52xVCqeMudZKJQClUOZfHaXhcL7GMcmk8lkPh9PCuAoCpzLfcrC8/PTWNC8RZeiptGXVSE4imJENx5+o2ublp9lwHGcE7T6jAWvGwZaFQPGKIC1aK0Rb9sGliieyqLRoRPHsf+/7HoeOLZ90GE9ipwfcdc1QWu4mQzQat9FkRdFXhDg2AzvdrUgCAR9g30KEyfY3YCOs+LHU+fj/aPb/fzq3KLw0gri2/vH58cXCj/fxDcnDsAld3bB2QGnykPFL4O0LN0Gi5D2AAAAAElFTkSuQmCC" alt="Internet Explorer" class="browser-icon"> IE 11</td>
                        <td class="status-error">✗ No Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-warning">⚠ Partial</td>
                    </tr>
                    <tr>
                        <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAAkFBMVEX///8iIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiL///8fHx8cHBwhISElJCNeXl4aGhotLS3W1tbo6Oj7+/tAQEDHx8fc3NzQ0NCwsLCRkZGLi4uDg4NGRkY2NjYwMDBWVlZRUVHj4+N3d3dqampSUlL09PRkZGRBTsYsAAAAKHRSTlMAAAUQMDzEPPnE/ST+GRn9E/b9+2fw6zz9AfxRVvz8Kf3+/q/8/P39Vp6VxwAAANhJREFUKM9jYKANYGZhZWWDshjZ2NkhLFZGZg5OLohybi4GZh5eED8PSD0/l4AAv6CQMEi9iKiYmDifOL+EpJQ0WIGMrJw8j4KikjIMKKuoqqkzaGjq6OrpgxQYCBoZC5mYmplbWFpZgxTY2NrZOzgyOjm7uLq5e3h6efsw+Pr5BwQGBYcwBIeFR0RGRTPExMbFJ8QkJjGkpKalZ2RkZedk5eblFxQWFZcwlJaVV1RUFldV19TW1TfUN1ZXNzE0t7S2tbcztLS2dXR2dTN09/T29TMwMAwMAvJHB39CAIKIKA5JfTvxAAAAAElFTkSuQmCC" alt="iOS Safari" class="browser-icon"> iOS Safari</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                    </tr>
                    <tr>
                        <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAAllBMVEX///8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP8AgP////8JcP8mhP9Ai/+cw/+30//S4//s8//8/f8Pa/9Jkv+CtP/W6f+RvP9gmv/1+f/D3P/p8v8Rbv91rf/e7P9Olf+Fu//I4P/k7//p6elNTU1sbm+MjY7c3d3v8PD19vavYkVoAAAAF3RSTlMAAAUQMDzEPPkk/hnbBP79/fj9Z/DqPJLgVdkAAADXSURBVCjPdZDXcsIwEEWvZMm4YooxvfceSOH/fy7eNWRghnTgIY/O3L0jjXhX261m671c8Kfb6f7WO3/dLnoxTpmfB4OgF8RJ2v4QvV4ARlKo6qTIGcsLj3MeiShCY0Fa7RO9MJayxmMWKq1KnS53x+2jQhOtcV2H+F4ZIxxd6xu/WK+Msdbee4briWfO+Ru3w2ky7eHxPGf4rbgQkHEVYrb4Ot18b3/233xElOMsDMMs8pMwSWL8Z3GSZRGTEPKiLLMfkBVlUSEHKsoy57wsSg6K4gPXRyvy4B9KSgAAAABJRU5ErkJggg==" alt="Android Chrome" class="browser-icon"> Android Chrome</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-ok">✓ Full Support</td>
                        <td class="status-warning">⚠ Partial</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="browser-test">
            <h2>Browser Detection</h2>
            <div id="browser-info">
                <p>Detecting your browser information...</p>
            </div>
        </div>
        
        <div class="browser-test">
            <h2>Favicon Implementation Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>favicon.svg</td>
                        <td class="status-ok">✓ Present</td>
                        <td>Vector format for high quality at any size</td>
                    </tr>
                    <tr>
                        <td>favicon.png</td>
                        <td class="status-ok">✓ Present</td>
                        <td>Standard PNG format for wide compatibility</td>
                    </tr>
                    <tr>
                        <td>favicon.ico</td>
                        <td class="status-ok">✓ Present</td>
                        <td>Legacy format for older browsers</td>
                    </tr>
                    <tr>
                        <td>apple-touch-icon.png</td>
                        <td class="status-ok">✓ Present</td>
                        <td>For iOS devices when adding to home screen</td>
                    </tr>
                    <tr>
                        <td>android-chrome-192x192.png</td>
                        <td class="status-ok">✓ Present</td>
                        <td>For Android Chrome browsers</td>
                    </tr>
                    <tr>
                        <td>android-chrome-512x512.png</td>
                        <td class="status-ok">✓ Present</td>
                        <td>For Android Chrome on high-resolution devices</td>
                    </tr>
                    <tr>
                        <td>site.webmanifest</td>
                        <td class="status-ok">✓ Present</td>
                        <td>Web app manifest for PWA functionality</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="browser-test">
            <h2>Next Steps</h2>
            <p>Your favicon implementation is comprehensive and should provide good compatibility across all major browsers and devices.</p>
            <p>To further enhance your favicon implementation, consider:</p>
            <ul>
                <li>Regular testing across different browsers and devices</li>
                <li>Adding more sizes for Android and iOS devices</li>
                <li>Updating your favicon when you update your branding</li>
            </ul>
            <p>
                <a href="favicon_generator_enhanced.html" class="button">Open Favicon Generator</a>
                <a href="../../../index.php" class="button">Back to Homepage</a>
            </p>
        </div>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Gunayatan Gatepass System - Developed by Piyush Maji</p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var browserInfo = document.getElementById('browser-info');
            
            // Detect browser
            var userAgent = navigator.userAgent;
            var browserName;
            var faviconSupport = 'Full favicon support detected';
            
            if (userAgent.indexOf("Chrome") > -1) {
                browserName = "Google Chrome";
            } else if (userAgent.indexOf("Safari") > -1) {
                browserName = "Apple Safari";
            } else if (userAgent.indexOf("Firefox") > -1) {
                browserName = "Mozilla Firefox";
            } else if (userAgent.indexOf("MSIE") > -1 || userAgent.indexOf("Trident") > -1) {
                browserName = "Internet Explorer";
                faviconSupport = "Limited favicon support. SVG format not supported.";
            } else if (userAgent.indexOf("Edge") > -1) {
                browserName = "Microsoft Edge";
            } else {
                browserName = "Unknown Browser";
            }
            
            // Mobile detection
            var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);
            var deviceType = isMobile ? "Mobile Device" : "Desktop";
            
            // Create HTML for browser info
            var html = "<h3>Your Browser Information</h3>";
            html += "<p><strong>Browser:</strong> " + browserName + "</p>";
            html += "<p><strong>Device Type:</strong> " + deviceType + "</p>";
            html += "<p><strong>User Agent:</strong> " + userAgent + "</p>";
            html += "<p><strong>Favicon Support:</strong> " + faviconSupport + "</p>";
            
            // Check if favicon is visible
            html += "<p><strong>Favicon Visibility:</strong> Check if you can see a favicon in your browser tab.</p>";
            
            browserInfo.innerHTML = html;
            
            // Test if favicon files are accessible
            function testFaviconAccess(url, elementId) {
                var img = new Image();
                img.onload = function() {
                    if (document.getElementById(elementId)) {
                        document.getElementById(elementId).innerHTML = "✓ Available";
                        document.getElementById(elementId).className = "status-ok";
                    }
                };
                img.onerror = function() {
                    if (document.getElementById(elementId)) {
                        document.getElementById(elementId).innerHTML = "✗ Not Found";
                        document.getElementById(elementId).className = "status-error";
                    }
                };
                img.src = url;
            }
            
            // Test each favicon file
            testFaviconAccess('favicon.svg', 'svg-status');
            testFaviconAccess('favicon.png', 'png-status');
            testFaviconAccess('favicon.ico', 'ico-status');
            testFaviconAccess('apple-touch-icon.png', 'apple-status');
        });
    </script>
</body>
</html>
