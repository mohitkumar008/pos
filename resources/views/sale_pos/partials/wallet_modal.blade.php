<div class="modal fade" id="walletModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <form  action="#" method="post" id="purchasewalletform">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="walletModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" class="form-control" id="wallet_type" name="wallet_type" value="">
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Mart User:</label>
                        <input type="text" class="form-control" id="mart_user" name="mart_user" value="NOIDA1" readonly>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Member ID:</label>
                        <input type="text" class="form-control" id="member_id" name="member_id" value="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Regd. Mobile No.:</label>
                        <input type="text" class="form-control" id="member_id" name="mobile_number" value="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Username:</label>
                        <input type="text" class="form-control" id="username" name="username" value="" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Grand Total:</label>
                        <input type="text" class="form-control" id="total_amount" name="total_amount" value="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Wallet Discount:</label>
                        <input type="text" class="form-control" id="amount" name="amount" value="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Net Total:</label>
                        <input type="text" class="form-control" id="net_total_amount" name="net_total_amount" value="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Wallet Otp:</label>
                        <input type="text" class="form-control" id="wallet_otp"  name="wallet_otp" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-6 text-left">
                            <a href="javascript:" class="resend-wallet-otp" onclick="false" >Resend OTP</a>
                            <a href="javascript:" class="otp-timer"></a>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" name="saveopt" id="walletsubmitbtn" >Proccess</button>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </form>
</div>
