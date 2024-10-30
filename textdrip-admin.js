jQuery(document).ready(function($) {
    $("#fetch-textdrip-campaigns").click(function() {
        const apiKey = $("#textdrip_api_key").val();
        
        // Make API call to fetch campaigns
        $.ajax({
            url: "https://api.textdrip.com/api/get-campaign",
            type: "POST",
            headers: {
                'Authorization': `Bearer ${apiKey}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            success: function(response) {
                $("#debugger").html(`Response received: ${JSON.stringify(response)}`);
                
                // Populate dropdowns with fetched campaigns
                $('.textdrip-campaign-select').empty();
                $('.textdrip-campaign-select').append('<option value="">Select Campaign</option>');
                
                // Store fetched campaigns to transient via an AJAX call to the server
                $.post(ajaxurl, {
                    'action': 'store_campaigns_to_transient',
                    'campaigns': response.data
                }, function(response) {
                    if (response.success) {
                        console.log('Campaigns stored successfully');
                    } else {
                        console.log('Failed to store campaigns');
                    }
                });

                for (const campaign of response.data) {
                    $('.textdrip-campaign-select').append(`<option value="${campaign.id}">${campaign.title}</option>`);
                }
            },
            error: function(xhr, status, error) {
                $("#debugger").html(`Error: ${error}`);
            }
        });
    });
});
