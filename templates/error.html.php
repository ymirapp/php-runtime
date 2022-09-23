<!--

__  __          _
\ \/ /___ ___  (_)____
 \  / __ `__ \/ / ___/
 / / / / / / / / /
/_/_/ /_/ /_/_/_/

-->

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?= $statusCode; ?> | <?= $message; ?></title>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="https://rsms.me/">
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />

        <!-- Styles -->
        <style>
            html, body {
                background-color: #19191e;
                color: #82c339;
                font-family: 'Inter var', ui-sans-serif, system-ui, -apple-system;
                font-weight: 300;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .flex-center-column {
                align-items: center;
                display: flex;
                justify-content: center;
                flex-direction: column;
            }

            .position-ref {
                position: relative;
            }

            .code {
                border-right: 2px solid;
                font-size: 26px;
                padding: 0 15px 0 15px;
                text-align: center;
            }

            .message {
                font-size: 18px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="flex-center-column position-ref full-height">
            <div style="padding-bottom: 20px;">
                <a href="http://ymirapp.com">
                    <svg class="h-6 w-auto" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" id="svg237056" viewBox="0 0 200 50.201561" height="50.201561" width="200" version="1.1" inkscape:version="1.0.1 (c497b03c, 2020-09-10)">
                        <sodipodi:namedview pagecolor="#ffffff" bordercolor="#666666" borderopacity="1" objecttolerance="10" gridtolerance="10" guidetolerance="10" inkscape:pageopacity="0" inkscape:pageshadow="2" inkscape:window-width="1440" inkscape:window-height="855" id="namedview118" showgrid="false" inkscape:zoom="0.84244792" inkscape:cx="270.35016" inkscape:cy="67.859997" inkscape:window-x="0" inkscape:window-y="23" inkscape:window-maximized="0" inkscape:current-layer="svg237056"></sodipodi:namedview>
                        <metadata id="metadata237062">
                            <rdf:rdf>
                                <cc:work rdf:about="">
                                    <dc:format>image/svg+xml</dc:format>
                                    <dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage"></dc:type>
                                </cc:work>
                            </rdf:rdf>
                            <rdf:rdf>
                                <cc:work rdf:about="">
                                    <dc:format>image/svg+xml</dc:format>
                                    <dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage"></dc:type>
                                    <dc:title></dc:title>
                                </cc:work>
                            </rdf:rdf>
                        </metadata>
                        <defs id="defs237060"></defs>
                        <g id="logo-group" transform="matrix(0.36989066,0,0,0.36989066,-89.384019,-116.93723)">
                            <g id="title" style="font-style:normal;font-weight:700;font-size:72px;line-height:1;font-family:'Meedori Sans';font-variant-ligatures:none;text-align:center;text-anchor:middle" aria-label="YMIR">
                                <path id="path237064" style="font-style:normal;font-weight:700;font-size:72px;line-height:1;font-family:'Meedori Sans';font-variant-ligatures:none;text-align:center;text-anchor:middle;fill:#b7d9a3" d="M 442.30962,95.016 V 120 h -9 V 95.016 L 411.27762,66 h 11.01601 L 438.34962,86.016 453.32562,66 h 12.024 z" transform="matrix(2.5,0,0,2.5,-786.54421,151.68)"></path>
                                <path id="path237066" style="font-style:normal;font-weight:700;font-size:72px;line-height:1;font-family:'Meedori Sans';font-variant-ligatures:none;text-align:center;text-anchor:middle;fill:#9acd6a" d="m 528.63537,65.784 v 54.288 l -9,-7.056 V 84.864 l -18.144,14.112 -18.072,-14.112 v 28.152 l -9.072,7.056 V 65.784 l 27.144,21.168 z" transform="matrix(2.5,0,0,2.5,-771.54421,151.68)"></path>
                                <path id="path237068" style="font-style:normal;font-weight:700;font-size:72px;line-height:1;font-family:'Meedori Sans';font-variant-ligatures:none;text-align:center;text-anchor:middle;fill:#82c339" d="m 543.6755,66 h 8.928 v 47.016 l -8.928,6.984 z" transform="matrix(2.5,0,0,2.5,-756.54421,151.68)"></path>
                                <path id="path237070" style="font-style:normal;font-weight:700;font-size:72px;line-height:1;font-family:'Meedori Sans';font-variant-ligatures:none;text-align:center;text-anchor:middle;fill:#65b700" d="m 609.55775,120 h -10.008 l -11.952,-22.032 h -20.016 V 89.04 h 25.992 q 1.656,-0.072 2.952,-0.864 1.152,-0.648 2.088,-2.088 0.936,-1.44 0.936,-4.104 0,-2.592 -0.936,-4.032 -0.936,-1.44 -2.088,-2.088 -1.296,-0.792 -2.952,-0.864 h -25.992 v -9 h 25.992 q 4.176,0.216 7.488,2.016 1.368,0.792 2.736,1.944 1.368,1.152 2.376,2.808 1.08,1.656 1.728,3.96 0.648,2.232 0.648,5.256 0,4.896 -1.584,8.064 -1.584,3.096 -3.6,4.896 -1.944,1.728 -3.744,2.376 -1.728,0.648 -2.088,0.648 z" transform="matrix(2.5,0,0,2.5,-741.54421,151.68)"></path>
                            </g>
                            <g id="tagline" style="font-style:normal;font-weight:500;font-size:32px;line-height:1;font-family:Montserrat;font-variant-ligatures:none;text-align:center;text-anchor:middle"></g>
                        </g>
                    </svg>
                </a>
            </div>

            <div class="flex-center">
                <div class="code">
                    <?= $statusCode; ?>
                </div>

                <div class="message" style="padding: 10px;">
                    <?= $message; ?>
                </div>
            </div>
        </div>
    </body>
</html>

