/**
 * Wordpress Auto Image Alt by Damien Lusson
 *
 * Add this script to your theme functions.php
 * Change the API keys in the code, you will need replicate and deepl, the price is 2.5$ per 10 000 image alt generated with both api.
 */

function add_image_alt_tag($post_ID) {
    $version = '2e1dddc8621f72155f24cf2e0adbde548458d3cab9f00c0139eea840d0ac4746';
    $image = wp_get_attachment_url($post_ID);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.replicate.com/v1/predictions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Token ****REPLICATE-API-TOKEN-REPLACE****',
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
        'version' => $version,
        'input' => array('image' => $image)
    )));

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    $retryCount = 0;
    while(empty($responseData['output']) && $retryCount < 5) {
        sleep(5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.replicate.com/v1/predictions/' . $responseData['id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Token ****REPLICATE-API-TOKEN-REPLACE****',
            'Content-Type: application/json'
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        $retryCount++;
    }

    if(!empty($responseData['output'])) {
        $blipresult = $responseData['output'];
        $blipresult1 = str_replace("Caption: ", "", $blipresult);

        // DeepL translation starts here
        $deeplApiKey = '****DEEPL-API-TOKEN-REPLACE****'; // Replace [yourAuthKey] with your actual DeepL API key
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api-free.deepl.com/v2/translate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: DeepL-Auth-Key ' . $deeplApiKey,
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'text' => $blipresult1,
            'target_lang' => 'FR'
        )));

        $response = curl_exec($ch);
        curl_close($ch);

        $translationData = json_decode($response, true);
        if (!empty($translationData['translations'][0]['text'])) {
            $translatedText = $translationData['translations'][0]['text'];
            update_post_meta($post_ID, '_wp_attachment_image_alt', $translatedText);
        }
    }
}
add_action('add_attachment', 'add_image_alt_tag');
