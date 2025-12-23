<!DOCTYPE html>
<html>

<head>
    <title>Domain WHOIS Lookup | APILayer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .whois-label {
            font-weight: 600;
            color: #555;
        }

        .whois-value {
            color: #212529;
            word-break: break-word;
        }

        .whois-row {
            margin-bottom: 0.75rem;
        }

        .spinner-border {
            display: none;
        }

        #lookup-btn[disabled] .spinner-border {
            display: inline-block;
        }
    </style>
</head>

<body class="bg-light py-5">
    <div class="container">
        <h2 class="mb-4">🔍 Domain WHOIS Lookup</h2>

        <form id="whois-form" class="mb-4">
            <div class="input-group">
                <input type="text" id="domain" class="form-control" placeholder="e.g. example.com" required>
                <button type="submit" id="lookup-btn" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Lookup
                </button>
            </div>
        </form>

        <div id="result" class="card d-none">
            <div class="card-body">
                <h5 class="card-title mb-4">WHOIS Information</h5>
                <div id="whois-output" class="row gy-2"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function addWhoisRow(label, value) {
            return `
                <div class="col-md-6 whois-row">
                    <div class="whois-label">${label}:</div>
                    <div class="whois-value">${value ?? 'N/A'}</div>
                </div>
            `;
        }

        $('#whois-form').on('submit', function (e) {
            e.preventDefault();
            let domain = $('#domain').val();
            let $btn = $('#lookup-btn');
            let $spinner = $btn.find('.spinner-border');
            let $output = $('#whois-output');

            $btn.attr('disabled', true);
            $spinner.show();
            $('#result').addClass('d-none');
            $output.html('');

            $.ajax({
                url: "{{ route('whois.lookup') }}",
                method: 'POST',
                data: {
                    domain: domain,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    const data = response.result;

                    $('#result').removeClass('d-none');

                    let content = '';
                    content += addWhoisRow('Domain Name', data.domain_name);
                    content += addWhoisRow('Registrar', data.registrar);
                    content += addWhoisRow('Registrar WHOIS Server', data.whois_server);
                    content += addWhoisRow('Updated Date', data.updated_date);
                    content += addWhoisRow('Creation Date', data.creation_date);
                    content += addWhoisRow('Expiration Date', data.expiration_date);
                    content += addWhoisRow('Name Server 1', data.name_servers?.[0]);
                    content += addWhoisRow('Name Server 2', data.name_servers?.[1]);
                    content += addWhoisRow('Domain Status', data.status);
                    content += addWhoisRow('Registrar Abuse Email', data.emails);
                    content += addWhoisRow('DNSSEC', data.dnssec);
                    content += addWhoisRow('Last Update', new Date().toISOString());

                    $output.html(content);
                },
                error: function (xhr) {
                    $('#whois-output').html(`<div class="text-danger">❌ Error: ${xhr.responseJSON?.message || 'Unknown error'}</div>`);
                    $('#result').removeClass('d-none');
                },
                complete: function () {
                    $btn.attr('disabled', false);
                    $spinner.hide();
                }
            });
        });
    </script>
</body>

</html>
