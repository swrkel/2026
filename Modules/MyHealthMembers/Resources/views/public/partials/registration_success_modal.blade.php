<div class="modal fade" id="myhealth_registration_success_modal" tabindex="-1" role="dialog" aria-labelledby="myhealthRegistrationSuccessTitle" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 680px;">
        <div class="modal-content text-left">
            <div class="modal-header" style="background:#00a65a;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myhealthRegistrationSuccessTitle">My Health Member Registration Completed</h4>
            </div>
            <div class="modal-body">
                <div id="myhealth-registration-success-print-area" style="font-family:Arial, sans-serif;">
                    <div style="text-align:center;margin-bottom:18px;">
                        <h3 style="margin:0 0 6px 0;">My Health Member Registration</h3>
                        <p style="margin:0;color:#666;">Please keep these details secure and confidential.</p>
                    </div>

                    <table class="table table-bordered" style="margin-bottom:15px;">
                        <tbody>
                            <tr>
                                <th style="width:35%;">Member Name</th>
                                <td>{{ $member->name }}</td>
                            </tr>
                            <tr>
                                <th>My Health Member Code</th>
                                <td><strong style="font-size:18px;letter-spacing:1px;">{{ $member->myhealth_code }}</strong></td>
                            </tr>
                            <tr>
                                <th>Temporary Passcode</th>
                                <td><strong style="font-size:22px;letter-spacing:1px;color:#d9534f;">{{ $passcode }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-warning" style="font-size:15px;line-height:1.5;">
                        <strong>Important:</strong> This passcode is shown only once. Please write it down, print it, or save it as PDF now. Do not share it with anyone except an authorized doctor, pharmacy, laboratory, or permitted business when you want to give access to your My Health records.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" onclick="window.print();">
                    <i class="fa fa-print"></i> Print / Save as PDF
                </button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Continue to Login</button>
            </div>
        </div>
    </div>
</div>
