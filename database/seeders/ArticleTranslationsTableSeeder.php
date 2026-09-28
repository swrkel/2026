<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ArticleTranslationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('article_translations')->delete();
        
        \DB::table('article_translations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'article_id' => 1,
                'title' => 'Add Expenses',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 6pt 0in 8pt 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Add Expenses &nbsp;</span></strong></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;">Click Expenses Module / Add Expenses</span></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9XyaAE7pDCYvhoI1bZ2lIwy4XlJ4W4CamfKDQzCo.png" /></span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">&nbsp;Add <strong>&ldquo;Select the Category&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Date&rdquo;</strong>.<strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Expenses For&rdquo;</span></strong></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Add </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Fleet&rdquo;</span></strong></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Expenses for Contact&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Total Amount&rdquo;</strong></span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/4Yjd9TF9snWbtBiWv2a6EuulC1EVi9Ze4WzyLj8F.png" /></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: red;">How to add Expenses Payment</span></strong></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Cash Payment</span></strong><span style="font-size: 14.0pt; line-height: 150%; color: black;"> </span></p>
<ol style="list-style-type: lower-alpha; margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Amount will show auto.</span></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select the Related Payment Type &ldquo;<strong>Petty Cash / Cash</strong>&rdquo; </span></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Accounting Module &ndash; Petty cash&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Add Note If needed.</span></li>
<li style="margin: 6pt 0in 8pt 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click &ldquo;<strong>Save or Save &amp; Print</strong>&rdquo;</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Q2vWK85t6MQCwwgLQyUqpsPTI1i8rXqSeETQnY49.png" /></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Bank Payment</span></strong></p>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Amount will show auto.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Select <strong>&ldquo;Payment Method - Bank&rdquo;</strong>.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Select <strong>&ldquo;Accounting Module &ndash; Bank Account&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Enter <strong>&ldquo;Cheque Number&rdquo;</strong>.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Select <strong>&ldquo;Cheque Date&rdquo;.</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Enter <strong>&ldquo;Payment Note&rdquo;</strong> If needed.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Click <strong>&ldquo;Save &amp; Print or Save&rdquo;</strong>.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/4xnvXWgQhKaafE4vaPTlNyyCFUxeLDdfLFajRzwo.png" /></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Credit Expenses Payment</span></strong></p>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Amount will show auto.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Payment Method &ndash; Credit Expenses&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Accounting Module &ndash; Account Payable&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Add Payment Note if needed.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click <strong>&ldquo;Save &amp; Print or Save&rdquo;</strong>.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/nDVMhQ4SsMLvs2vKA5b8rxCTkG6Jtt9pM5V3KG20.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-06 07:46:35',
                'updated_at' => '2024-12-06 07:46:35',
                'deleted_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'article_id' => 2,
                'title' => 'Add New Contact',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="background: yellow;">Add New Customer</span></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;Go to Contact Module</p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;Customer</p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/2Yf2Uj5zdSb8oW80mi3IBcIWj5W0tIKvpZnSvRWo.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/dElCXrXb7SpZXrcrQM1dLC04kW3v8QFkW2ghwGfB.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Contact Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Customer Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Need to send SMS Type&rdquo;&nbsp; &ldquo;Yes or No&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Credit Notification Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Tax Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;VAT Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Opening Balance&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Pay term&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Customer Group&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Credit Limit&rdquo;</strong></li>
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Transaction Date&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/0YVL11P8MuTy9TWe0wfyUGoZgRQCPAqoP7UOdcwr.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="13">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Email&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Altranate contact number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Land line number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Address, Address line 2, Address line 3&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;City, State, Country and Land mark&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;NIC or Passport No&rdquo;</strong></li>
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save&rdquo; button.</strong></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/FuWsBkw65b5bGjzbxRawGl9JNiIfnW7HfoMgjdGb.png" /></p>
<p style="margin: 0in 0in 10pt 0.25in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-06 08:11:48',
                'updated_at' => '2024-12-06 08:11:48',
                'deleted_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'article_id' => 3,
                'title' => 'Add New Product',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Add New Product</span></strong></li>
</ol>
<p style="margin: 6pt 0in 8pt 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;">Product Module/Add product</span></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;">&nbsp;</span></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/7DsSXk46y0Vp2gtNrGb3FELAHIEmcr5tS3Gs9QFl.png" /></span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add<strong> &ldquo;Product Name&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Product code will come auto if need can add own <strong>&ldquo;Code&rdquo; (SKU)</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the applicable units</li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the Category<strong> &ldquo;Other Items&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the Sub Category<strong> &ldquo;Lubricants&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">If need add<strong> &ldquo;Alert Qty&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the Stock Account<strong> &ldquo;Finished Goods Account&rdquo; or appropriate account from the drop down list</strong></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/xWNJfckr7BLIqVSpoovIV4l29B2f8zDN8jhDPN93.png" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="8">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Item<strong> &ldquo;Purchase Price&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Item<strong> &ldquo;Selling Price&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add Save &amp; Opening Stock&rdquo; </strong></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/C2dFE33NPf76eYHKKKaXZMZxT1jrXwDswDmOA0eM.png" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="11">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add the <strong>&ldquo;Opening Qty&rdquo;</strong> here<strong> (if there any opening stock)</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save Button&rdquo;</strong></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/4xyvsO8jklTPhGWa0tOzDX3ZQZhff8D768qodIXB.png" /></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">If purchased any new item to the list Add without opening qty. after added 1 to 9 details, Click Save Button</span></strong><strong><span style="font-size: 14.0pt; line-height: 150%;">. </span></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>',
                'language' => 'en',
                'created_at' => '2024-12-06 09:59:52',
                'updated_at' => '2024-12-06 09:59:52',
                'deleted_at' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'article_id' => 4,
                'title' => 'Product Price Change Steps',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in; text-align: center; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; color: red; background: yellow;">Price Change Steps</span></strong></p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 0in; margin-bottom: 0in;">
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Product Module</li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">List Product</li>
</ol>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/rzl9gXPQQi0BG2ozQ8KRwlH0GzF7MGjNL0Px2r1P.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="3">
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Action&rdquo;</strong> (Search by category or product name and click action button)</li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Go to <strong>&ldquo;Edit&rdquo;</strong></li>
</ol>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/V5B5B68wqYfiXCdKNxw7Qcd71P65C1UPBhxZsbLN.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="5">
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add the <strong>&ldquo;Purchase Price&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add the <strong>&ldquo;Selling Price&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click<strong> &ldquo;Update&rdquo;</strong> it.</li>
</ol>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ZvpajtUhYl563Po7FsPdoL9EFIsP7BE4M4xorhEk.png" /></p>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-06 10:09:55',
                'updated_at' => '2024-12-06 10:09:55',
                'deleted_at' => NULL,
            ),
            4 => 
            array (
                'id' => 5,
                'article_id' => 5,
                'title' => 'Add New Pump Operator',
                'content' => '<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 115%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 115%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Petro Module/Pumper Management</span></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/zJ0jrugXAlk2Qk0g176gBQyqsSsTcvdbUQWbjrYs.png" width="241" height="385" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="2">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select &ldquo;Pump Operator&rdquo;</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Add&rdquo;</span></strong><span style="font-size: 14.0pt; line-height: 150%;"> button</span></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/m9zBQEINhEPCzrHzIfJppZUSEjsCmIEi1aRcP7Gz.png" width="624" height="267" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="4">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Name&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Address&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Mobile&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Land Line Number&rdquo;</strong><strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Date of birth&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;National ID Number&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Email Address&rdquo;</strong> <strong>&nbsp;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;User Name&rdquo;</strong><strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Passcode&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Re-enter passcode <strong>&ldquo;Confirm Passcode&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Opening Balance&rdquo;</span></strong><span style="font-size: 14.0pt; line-height: 150%;"> if needed</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Location&rdquo;</span></strong></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Gs9Qog53bRo0LtAq634gnhQVZ60DcKsiXNYVADGe.png" width="624" height="543" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="16">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Commission Type&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Transaction Date&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Use for Admin\'s Operator Dashboard Login:*&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Can Minimize Full Screen:*&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click <strong>&ldquo;Save Button&rdquo;</strong></span></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/VUIy7WexjMb50eHkTCV9TX8gMYLBGE0KB3ne9qSJ.png" width="624" height="283" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-06 10:17:19',
                'updated_at' => '2024-12-06 10:17:19',
                'deleted_at' => NULL,
            ),
            5 => 
            array (
                'id' => 6,
                'article_id' => 6,
                'title' => 'Add New Supplier',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: #c00000; background: yellow;">Add New Supplier</span></strong></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Contact Module</span></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Supplier</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Click <strong>&ldquo;Add&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ysC9OzdUFDQTZYOeTSpBuxcNcCDUKJlnbWLAdIfT.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Contact Type&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Enter <strong>&ldquo;Supplier Name&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Auto Loaded Contact ID</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Enter <strong>&ldquo;Opening Balance&rdquo;</strong> If any.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Transaction Date&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Enter <strong>&ldquo;Whatsapp Number (Mobile No)&rdquo;</strong></span></li>
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Finally click <strong>&ldquo;Save&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Ui1Sj9X82nIbgiO9tL69SUobMc6NzB5FaS6xNB36.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-06 10:25:46',
                'updated_at' => '2024-12-26 06:56:28',
                'deleted_at' => NULL,
            ),
            6 => 
            array (
                'id' => 7,
                'article_id' => 7,
                'title' => 'Add New Purchase',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u>&nbsp;</u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><span style="font-size: 18.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><span style="font-size: 18.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, </span></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><span style="font-size: 18.0pt; line-height: 107%; color: #000066;">Log on and Go, IT\'S REAL SIMPLE</span></em></strong></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Add Purchase Orders </span></strong></li>
</ol>
<ol style="margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Purchase Module&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;Click <strong>&ldquo;Add Purchase&rdquo;</strong></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/C80NB6C2Y7YnbZ1qBbwszS8SwnMsUiuOvTL12SP8.png" /></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;" start="3">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Supplier Name&rdquo;</strong></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Invoice Number&rdquo;</strong></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Received Date&rdquo;</strong></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Invoice Date&rdquo;</strong></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Status &ldquo;<strong>Received</strong>&rdquo;. If always receive with the bill, you can set to auto load, in the settings / system settings / purchases</li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Vat Invoice (Yes or No)&rdquo;</strong></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Status &ldquo;<strong>Main Store</strong>&rdquo;.</li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Pay Terms (<strong>Number of Days or Months - Based on Payment Type select</strong>)</li>
<li style="margin: 6pt 0in 8pt 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the item (Search by Code or Name. at least 2 characters needed.)</li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/xK1aaNX8wVU1CAHl7DOTaxOLNRvW3ByZ9Jo5tUYX.png" /></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;" start="12">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter the <strong>&ldquo;Order Qty&rdquo;</strong> Check the Purchase Price if Need can Change. Do the changes. Check the Final item total Amount of the Bill in &ldquo;Line Total&rdquo; column</li>
<li style="margin: 6pt 0in 8pt 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Check the <strong>Final Total Amount</strong> of the Bill.</li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/FO0F5xHyqAT7Y8gTsqscNoFD0cMF8CaWJEPzuMbi.png" /></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;" start="14">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Unload the Stocks to related Tank or Tanks. Make sure to match the total purchased quantity with the distributed quantities.</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/vDixjdgT6jmqLQGgVyigxQeihFc8QSZmdWWSp0xR.png" /></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Next</span></strong></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Add Supplier Payments</span></strong></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;" start="15">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><a name="_Hlk183935352"></a><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Cash</span></strong></li>
</ol>
<ol style="list-style-type: lower-alpha; margin-bottom: 0in; margin-top: 0px;">
<li style="text-align: justify; margin: 0in 0in 0in 24px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Check Amount to Pay.</li>
<li style="text-align: justify; margin: 0in 0in 0in 24px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the correct <strong>&ldquo;payment method&rdquo;</strong>. <a name="_Hlk183935677"></a>If Cash Payment Select&nbsp; and enter related details. Enter the relavent details as per the payment method selected.</li>
<li style="margin: 6pt 0in 6pt 24px; text-align: justify; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Accounting Module &ndash; Cash &rdquo;</strong>.</li>
<li style="margin: 6pt 0in 6pt 24px; text-align: justify; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add note if needed.</li>
<li style="margin: 6pt 0in 6pt 24px; text-align: justify; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>Save</strong> button.</li>
</ol>
<p style="margin: 6pt 0in; text-align: justify; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/IeMbhX8GlOibaXfyB6QQMi2dZgOhKs6AraNDCwZR.png" /></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;" start="16">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><a name="_Hlk183936020"></a><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Cheque</span></strong></li>
</ol>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 6.0pt;">
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Check Amount to Pay.</li>
<li style="margin: 0in 0in 0in 24px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Payment method&rdquo;</strong> <a name="_Hlk183936689"></a>If Cheque Payment Select&nbsp; and enter related details. Enter the relavent details as per the payment method selected.</li>
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Accounting Module &ndash; Cheque in Hand &rdquo;</strong>.</li>
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Bank Name&rdquo;</strong>.</li>
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date or Date Range&rdquo;</strong>.</li>
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Cheque&rdquo;</strong>. If any.</li>
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add payment note if needed.</li>
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>save</strong> button.</li>
</ol>
<p style="margin: 6pt 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cVyHwnkBgN3yEz6cpOWy4J4vPBc70aLKaFkubx9L.png" /></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;" start="17">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Bank Transfer</span></strong></li>
</ol>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Check Amount to Pay.</span></li>
<li style="margin: 0in 0in 0in 24px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select </span><strong><span style="color: black;">&ldquo;Payment method&rdquo;</span></strong><span style="color: black;"> <a name="_Hlk183937135"></a>If Bank transfer Payment Select and enter related details. Enter the relavent details as per the payment method selected.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select </span><strong><span style="color: black;">&ldquo;Accounting Module &ndash; Bank Account&rdquo;</span></strong><span style="color: black;">.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Add </span><strong><span style="color: black;">&ldquo;Cheque No&rdquo;</span></strong><span style="color: black;">. If any.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Add </span><strong><span style="color: black;">&ldquo;Cheque Date&rdquo;</span></strong><span style="color: black;">.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Add note if needed.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Finally click </span><strong><span style="color: black;">save</span></strong><span style="color: black;"> button.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cr9BozIQWxTamPEOJZilC9ZOb10aYkZpYlDKE8tt.png" /></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;" start="18">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Credit Purchase</span></strong></li>
</ol>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Check Amount to Pay.</span></li>
<li style="margin: 0in 0in 0in 24px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Payment method&rdquo;</strong> If Credit purchase Payment Select and enter related details. Enter the relavent details as per the payment method selected.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Accounting module &ndash; Account Payable &rdquo;</strong>.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Add Note If needed.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Finally click <strong>save </strong>button.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/aD9hrEVojfc6bvfcZxzwyK7nBasHMKR1FtYDaxtk.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-06 10:36:26',
                'updated_at' => '2024-12-06 10:36:26',
                'deleted_at' => NULL,
            ),
            7 => 
            array (
                'id' => 8,
                'article_id' => 8,
                'title' => 'Cash Deposit',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 4.0pt; line-height: 107%; color: #000066;">&nbsp;</span></strong></p>
<ol style="margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Cash Deposit Steps</span></strong><strong><span style="font-size: 14.0pt; line-height: 150%;"> </span></strong></li>
</ol>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Accounting Module&nbsp;</span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">List Accounts </span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/lZmR8GdUSfTGKJ5Q0BT2nACtxadUNoVdkVrWsYvv.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="3">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Search &ldquo;<strong>Cash</strong>&rdquo; or Click &ldquo;<strong>Cash Deposit&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click &ldquo;<strong>Cash Deposit Button</strong>&rdquo;</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/zwZRPtHP1QAucoshSxfQ1RH0Mai8AgvFFqtNfYVt.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="5">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Account Group&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Deposit To&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Date&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Enter <strong>&ldquo;Amount&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click <strong>&ldquo;Submit Button&rdquo;</strong></span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tDatOdLgl5RxWb2Ocr6YxxLn91Cc5vLeM1NJ10u9.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 08:01:02',
                'updated_at' => '2024-12-09 08:01:02',
                'deleted_at' => NULL,
            ),
            8 => 
            array (
                'id' => 9,
                'article_id' => 9,
                'title' => 'Cheque Deposit',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066; background: yellow;">Cheque Deposit</span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Accounting Module </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">List Account </span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/eM33ooTTmYhWmDqOulLhxRJue6O7cMV4SYcfMmoN.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="3">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click the &ldquo;<strong>Cheque Deposit Button</strong>&rdquo; </span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/zYe2veQn8RjdwAGduvZYI0WEbXFZt0hiVME0NOm5.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="4">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the <strong>"Transaction Date"</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Date Range and Created on date&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Tick the <strong>&ldquo;Cheque Number&rdquo; </strong></span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Sh30nuK89YXcqASxz9QVcxDpmoXrlV4e86aLK2Ec.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="7">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the <strong>&ldquo;Deposit To&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add not it needed.</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click the <strong>&ldquo;Submit&rdquo;</strong> button - <span style="background: lime;">cheque deposit work done.</span> </span></li>
</ol>
<p style="line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 6pt 0in 8pt 72px;"><span style="font-size: 12.0pt; line-height: 150%;"><span style="background: lime;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/f52oFKAnWSdx7WRxzHdl1oMa5R9WtCcPdRPMbQH9.png" /></span></span></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 08:03:47',
                'updated_at' => '2024-12-26 07:04:22',
                'deleted_at' => NULL,
            ),
            9 => 
            array (
                'id' => 10,
                'article_id' => 10,
                'title' => 'Bank Deposit',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Bank Transfer</span></strong></li>
</ol>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Accounting Module </span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">List Accounts </span></li>
</ol>
<p style="margin: 6pt 0in 8pt 1.75in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/wUvILhhkntOu3eAlk5f16gteLSACEtayaTB4U5Cg.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="3">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Search &ldquo;<strong>Bank</strong>&rdquo;</span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select &ldquo;<strong>Transfer Account</strong>&rdquo; </span></li>
</ol>
<p style="margin: 6pt 0in 8pt 1.75in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/6lSE7btr5RIbFEB48089JdtZsve2UvHE2pbTXHYr.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="5">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Accounting Group&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Transfer To&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Enter <strong>&ldquo;Amount&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Enter <strong>&ldquo;Cheque Number&rdquo;</strong> If needed.</span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Transaction Date&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Tick the <strong>&ldquo;Transfer&rdquo;</strong></span></li>
</ol>
<p style="margin: 6pt 0in 8pt 1.75in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/lKbKYM8EJU2x6LoNmuwsXYS6P758E8zdy2rooek2.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="11">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add <strong>&ldquo;Note&rdquo;</strong> If needed.</span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Finally click <strong>&ldquo;Save&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt 1.75in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 6pt 0in 8pt 1.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ED2hDu1gSAsKuZuT6gb1JJhyQzXAfy4J1K5urNHy.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 08:07:46',
                'updated_at' => '2024-12-09 08:07:46',
                'deleted_at' => NULL,
            ),
            10 => 
            array (
                'id' => 11,
                'article_id' => 11,
                'title' => 'Card Deposit',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 4.0pt; line-height: 107%; color: #000066;">&nbsp;</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Card Deposit Steps</span></strong><strong><span style="font-size: 14.0pt; line-height: 150%;"> </span></strong></li>
</ol>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Accounting Module </span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">List Accounts </span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/gfAZb2BGSWPVycJOb2y71mZDKdZNfa06G4AZILKe.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="3">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click &ldquo;<strong>Card Deposit Button&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/nzFmaezjHlkAf8F169l6K5gD81yTI7I5IvVRhlsX.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="4">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Card Account&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Account Group&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Deposit To&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Date&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Enter <strong>&ldquo;Cheque Number&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Enter <strong>&ldquo;Amount&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add note if needed.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Neye2v3PNdbZLhslS5cQjaVUzqubheb2rplGYCuU.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="11">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Finally click <strong>&ldquo;Save&rdquo;</strong> button.</span></li>
</ol>
<p style="line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 6pt 0in 8pt 120px;"><span style="font-size: 12.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ZKoZbcFrvwhJOYa6ZYv8iJzLUFOgQWEVwqVPZvsv.png" /></span></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 08:10:19',
                'updated_at' => '2024-12-26 07:06:20',
                'deleted_at' => NULL,
            ),
            11 => 
            array (
                'id' => 12,
                'article_id' => 12,
                'title' => 'How to Create New Account',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">How to create New Account</span></strong><strong><span style="font-size: 14.0pt; line-height: 107%; color: red;"> </span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red;">Example for Loan Account</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Accounting Module</span></li>
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">List Account</span></li>
<li style="margin: 0in 0in 8pt 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/lo8KtTAmKdjaHU8s4nkvw4aW6djEBueU0AlfK1Xm.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Location&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Enter <strong>&ldquo;Account Name&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Account Type&rdquo;</strong>. According to the current accounting structure, you can select either <strong>&ldquo;Main Account or Sub Account&rdquo;</strong>. In case issues select sub account it is necessary account too.</span></li>
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Show in balance sheet&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Account Group&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/SJeXl5ZILq93eZmHdgmOi6EysIkNxEpH3s4aNctS.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="9">
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Enter <strong>&ldquo;Account Number&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Add note if needed.</span></li>
<li style="margin: 0in 0in 8pt 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Finally click <strong>&ldquo;Save&rdquo; </strong>button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/G96bf2VzIUHaxFm3evilaqZg3DHv3QM8GDK4yAvW.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Accounting module/ List account/Account group</span></li>
</ol>
<p style="margin: 0in 0in 8pt 1in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">You can check if the configured group exists.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/NJJa8w5FOZkawgtfxzbDpN7agO3rdpV0yVzpNNNl.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 08:29:54',
                'updated_at' => '2024-12-26 07:08:53',
                'deleted_at' => NULL,
            ),
            12 => 
            array (
                'id' => 13,
                'article_id' => 13,
                'title' => 'Add Credit Limit',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Customer Credit Limit</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Add a new contact</span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Contact Module/Customer/Add new customer/Credit Limit</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #00b0f0;">Already Added Customer Limit Changes</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Go to Settings</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Business Settings</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/bY06uivCfrfO8TcYJcTnRCvOKeOhIi4E5MZvx4LK.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Customer &amp; Supplier Page</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select Customer Page</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select the fields which you want to show when adding or editing the contacts.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/EcqY67x0xkadK95z2R49y7ers8SsFSmatMSKKQSz.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="7">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Update Settings</span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/5KbB2DBD56FSfxNnMLWRgAov5qDWaqzAN3wdXsuf.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="8">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Go to Contact Page </span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select Customer</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>"Action"</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>"Edit"</strong>.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/eTnSfTxfu9q1U4RP6tWVZuKaTOj706vQSmJULv8y.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Add the credit limit and update.</span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/C4IH7skxeIrmldpK1h3GnUSk5qByoht6AnmlMOQ1.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 09:23:04',
                'updated_at' => '2024-12-26 07:11:12',
                'deleted_at' => NULL,
            ),
            13 => 
            array (
                'id' => 14,
                'article_id' => 14,
                'title' => 'Customer Deposit',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Customer Deposit</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Go to Contact Module</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Customer&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/VqQv4MOevHmhyR40sCCFAhQoGEVFIgFwmPscnCsl.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Action&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Security Deposit&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/7g4I2kVd7S3AKlAtVNwbdMuKsfhsyTh8hpEiukqv.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Type Deposit Amount</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Payment Method&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select the <strong>&ldquo;Transaction Date&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Accounting Module&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Current Liability Account&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Finally click <strong>&ldquo;Save&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/2X3yFR4Uqcc8mgFYwx9jZ27x2ResX7XZ0UqSgFTk.png" /></p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-09 09:25:33',
                'updated_at' => '2024-12-09 09:25:33',
                'deleted_at' => NULL,
            ),
            14 => 
            array (
                'id' => 15,
                'article_id' => 15,
                'title' => 'Refund Deposit',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Add Customer Refund Deposit</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Contact</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Customer</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/0Fp6W7P9nf4ROOHQzi2qbabbDmYf5tUtZPIaBd0F.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Action&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Refund Deposit&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/AN9jPcI0hp1tPxgQcAB3m9VuS1Ms4zhUrryNCYu6.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter the <strong>&ldquo;Refund Deposit Amount&rdquo;</strong>.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select the <strong>&ldquo;date&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select the <strong>&ldquo;Payment Method&rdquo;</strong> (Refunding from Bank or Cash)</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select the <strong>&ldquo;Accounting Module Account&rdquo;</strong>. (Based on the payment method)</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;Current Liability Account&rdquo;</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Add Payment <strong>&ldquo;Note&rdquo;</strong>: Optional</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Finally click <strong>&ldquo;Save&rdquo;</strong>. </span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/XpkvH2EhjcAwRadPT10vPtHShQsWY6zw3g7sOUOJ.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Please make sure to get the backup before enter the refund Deposit.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Once added the cheque please check all the details and let us know if anything. </span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">How to check the Refund Deposit details?</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Go to Contact Module</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Customer</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Action&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Ledger&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Security Deposit&rdquo;.</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Go to Accounting Module Select the related account / Action / Account Book.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">(Select the date duration details will load automatically)</span></li>
</ol>',
                'language' => 'en',
                'created_at' => '2024-12-09 09:35:58',
                'updated_at' => '2024-12-26 07:12:31',
                'deleted_at' => NULL,
            ),
            15 => 
            array (
                'id' => 16,
                'article_id' => 16,
                'title' => 'Cheque Return',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Add Customer Cheque Return Steps</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Contact Module</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Customer</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tqBlOjI9lgRtzD5J5gYlp3ZybC7qorHluItfOcVx.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Action&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Refund / Cheque Return&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ghvxzlto8tWPoTV9jN1qtvxMPrYdRkx1JcllIWj5.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Type &ndash; Cheque return&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Cheque return amount&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the <strong>&ldquo;Paid on Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Payment method&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add payment note if needed.</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save&rdquo;</strong>.</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/dv7DeyGZs0ESSE7DMhbfdUQpdacgZ5jMtuWfZtnz.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Please make sure to get the backup before enter the cheque return.</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Once added the cheque please check all the details and let us know if anything.</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">How to check the Returned Cheque details?</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Go to Accounting Module</p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Search <strong>&ldquo;Returned Cheques&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Yq3GGulBQQ24bIFIwhqkkyzthgvpYpPh5GJIbczY.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">(Select the date duration details will load automatically)</p>',
                'language' => 'en',
                'created_at' => '2024-12-09 09:55:39',
                'updated_at' => '2024-12-26 07:13:52',
                'deleted_at' => NULL,
            ),
            16 => 
            array (
                'id' => 17,
                'article_id' => 17,
                'title' => 'Customer Balance - Customer Ledger',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Customer Balance &ndash; Customer Ledger</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Contact Module</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Customer</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/fgOQ5bw30wTyEGlaGOO9F96Yc8bkfODU8FIeIitY.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Action&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Ledger&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/VK7MA98Cxkeery7R2qhTyoTefgoPqmrKp5f5LKex.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Date Range&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Showing all account summery details.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/MLzm7FPElcul4xr9uYuvC6lIjgbkp6fq9wICXURH.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="7">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Showing all invoices and payments.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/h8HCP4JWUpPJTm8C011qLlEelcWETpQ2G2Sya0l5.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 09:58:24',
                'updated_at' => '2024-12-26 07:14:37',
                'deleted_at' => NULL,
            ),
            17 => 
            array (
                'id' => 18,
                'article_id' => 18,
                'title' => 'Settlement Steps',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #00b050;">We have done many improvements in the settlement page and due to the same, entering credit sales needs to be performed as below mentioned steps.</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #00b050;">Please make sure to follow the same in order to get the best results without any errors.</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Petro Module</span></p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Settlement</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Od4mWp9mfJbrL1uX4M2fY4qNodV0CmpnbnTaka1o.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Pump operator&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Transaction Date&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Meter Sales&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Pump No&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Pump Closing meter&rdquo;</strong>. Pump starting meter, Sold qty and Unit price are auto loaded.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Testing Qty&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Check all added details and Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cwbwDt2RQ0KXk9jztTbzZ2tSwSuad8wQAcpjCIJ8.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="8">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><a name="_Hlk184029216"></a><span style="font-size: 12.0pt; line-height: 107%;">Shows all meter sales details are in the below section</span><span style="font-size: 12.0pt; line-height: 107%;">.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Total quantity of fuel sold by every pump operator.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/pVyMWpbWd6c6L57UcY0y4mTBPVyPgDJmIPzDYImG.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="10">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Other sales&rdquo;</strong> tab section.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Store&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Other sale Items&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Balance stock and Unit price are auto loaded.</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="13">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Other sale quantity&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Shows all Other sales details are in the below section.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/a9dK1lsPAIVEjjycCpk8jGBnXxNF6x7XpHSNi13r.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Payment&rdquo;</strong> tab section and check payment details.</span></p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Payment to finalize&rdquo;</strong> button.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/kv1ZriPwJe6duPDnEuXOMint29hH8fQSDTB5Ydj6.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="16">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Sale Amount- Cash&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Customer&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Sales amount&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/SgIhMnC8zTT64R96hBVblNjC7jP3p6bTsLXdpF6V.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="20">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Card&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Customer&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Card Type&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Card Number&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Card payment amount&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Slip Number&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Add note if needed.</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="26">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/DFBEXIbzUpVEHUdD8P1YYfGuS227UoWUm9OQSduE.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="27">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Credit sales&rdquo;</strong> </span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Credit sale customer&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Order number&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Order Date&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Customer Vehicle Number&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Product&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Quantity&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Credit sale amount&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Czy4YWuv8wRs21g5IvtdAVzPCmeA1yb98J0w7cRL.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="36">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Shortage&rdquo;</strong>. <a name="_Hlk184031228"></a>If the total amount is less than the total paid, the balance should be shown under shortage.</span></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">If the total amount is more than the total paid, the balance should be shown under <strong>&ldquo;Excess&rdquo;</strong>.</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="37">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Amount&rdquo;</strong>. Add note if needed.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ZVbo80GmSrC7sYLAR0gFHnxfxQuw1IfbydI8u7TN.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="39">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Finally click <strong>&ldquo;Save&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ILnfF0AjjsQ0biUwIKcaMsLY16fDii0JjrKHZs0e.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Check entered settlement details, go to petro module/list settlement/Action/view.</span></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Settlement with Discount</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="40">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">If any discount is given for sold products, mention the given discount in this Settlement either in Meter Sales, Other sales, other Income sections (image No 40,41,42 &amp; 43)</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/7kExATXirxy1W4CZIBaDLP1fWRVeXPwUxswGvDYz.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="44">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Credit sales&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Enter <strong>&ldquo;Discount amount&rdquo;</strong> If any discount is given, enter the discount amount in image No 45 position.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Check discount total.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/92IhZYUDkQPDgoEqGPqS51ATQB1GJwyZoJ0UJHAb.png" /></p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-09 10:21:38',
                'updated_at' => '2024-12-09 10:21:38',
                'deleted_at' => NULL,
            ),
            18 => 
            array (
                'id' => 19,
                'article_id' => 19,
                'title' => 'Pumper Dashboard All Steps',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Add Pumpm Operator</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Petro Module/Pumper Management</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;">&nbsp;</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9dSlXbybywsyarPpUn3BUDD1ZnK5n4ugCBNTL31c.png" /></span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;Pump Operator&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click </span><strong><span style="font-size: 12.0pt; line-height: 107%;">&ldquo;Add&rdquo;</span></strong><span style="font-size: 12.0pt; line-height: 107%;"> button</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;">&nbsp;</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/2Uf7Y59IT9MKlITvExtLgfFpOpOD2QeLSI3Hcb2h.png" /></span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Name&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Address&rdquo;</strong> </span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Mobile&rdquo;</strong> </span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Land Line Number&rdquo;</strong><strong> </strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Date of birth&rdquo;</strong> </span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;National ID Number&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Email Address&rdquo;</strong> <strong>&nbsp;</strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;User Name&rdquo;</strong><strong> </strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Passcode&rdquo;</strong> </span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Re-enter passcode <strong>&ldquo;Confirm Passcode&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter </span><strong><span style="font-size: 12.0pt; line-height: 107%;">&ldquo;Opening Balance&rdquo;</span></strong><span style="font-size: 12.0pt; line-height: 107%;"> if needed</span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select </span><strong><span style="font-size: 12.0pt; line-height: 107%;">&ldquo;Location&rdquo;</span></strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;">&nbsp;</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/wTrWdpwRqJjvFfdFdm0A50tpd6OVEObIQN3hsHtE.png" /></span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="16">
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Commission Type&rdquo;</strong> </span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Transaction Date&rdquo;</strong> </span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Use for Admin\'s Operator Dashboard Login:*&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select <strong>&ldquo;Can Minimize Full Screen:*&rdquo;</strong></span></li>
<li style="margin: 0in 0in 8pt 72px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Save Button&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/jVuiRKbBsvhvonNsuOZsbyYGFVfSAvcEfpS1MYa0.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Assign Pumps</span></strong></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="1" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Black color button</strong>&rdquo;.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Petro Module</strong>&rdquo;.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Pumper Management</strong>&rdquo;.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Nan3MAuMufksR9QbH0mhd5ERJxrCbREU2HPCBZ4A.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="4" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Daily Pump Status</strong>&rdquo;.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/FG4Q4mZPAaJG4OzkQXRYORXrvPIqUklB2QrO2G5s.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="5" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;+ Assign&rdquo;</strong> Button.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Pump Operator</strong>&rdquo;.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Pump</strong>&rdquo;.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Submit</strong>&rdquo; Button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/iXeYBimwenF2GrQ17CTgIJeUZKSfIrINuZA0TDPL.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Pumper Dashboard Login</span></strong></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="1" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">In the system login page</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Login</strong>&rdquo; button</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/sOaGrGW0HsrWlNrxzKZOsfwuhKoTKvCuU9c3rHBS.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="2" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select Your &rdquo; <strong>Business</strong>&rdquo;</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select your &ldquo;<strong>Login Display</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/vaXE3nXtXX93otuzx8kGxzHCIOlVt8cY52WpHm3c.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;">Now you can see the pumper dashboard login page</span></strong></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="4" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Pump Operator</strong>&rdquo; password.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click green color button &ldquo;<strong>Click to enter</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/CVXuaF2xvFsgR0UpsV8q8Rl8qJIVXDfaIAomVwCh.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;">Pumper Dashboard/Login dashboard</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Receive Pump</strong>&rdquo;</span></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="6" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click selected pump</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tvaGcIq0srX4lgoJfe1B0mDNr767isrlJaK3aKTb.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="7" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Reconfirm meter</strong>&rdquo;</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Confirm meter Reading</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cX2yVSm6Lo4U2b9lpuhhqGKGBgDJbdcguZOPSNyr.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Pumper Dashboard Payments (No enter Meters)</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Cash</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Cash Amount</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Amount Correct? Click here</strong>&rdquo; button.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Save&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9lU0uEPyAbZxkRh2XeXA3k2AWfGqjaJadg2B9rmq.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Card</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/xh6oN3kfkFkUo1hd6jYeezZ0s5gZBnsxf2Ew8hx9.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="6">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Card Type</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Amount</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Add button</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Save button</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/kXBdemPruBwDKekBsYMyUPpZ6N1fjwCidJC93OOw.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="10">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Cheque</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Customer</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Bank</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Cheque Number</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Cheque Date</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Cheque Amount</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Finalize</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Evkfvl7a4g4vvm6BfGLDKOVha6819FOqkHF7a3cd.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="17">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Credit</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cHyTZCmu8v3Sf8Vw7iFRwAJ8AFzuiWHKPeyUFVRH.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="18">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Customer</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Order Number</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Order Date</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Customer Vehicle Number</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Product</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Qty</strong>&rdquo; (Quantity)</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Auto Load Amount (Before Discount)</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Customer vehicle number</strong>&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Add</strong>&rdquo; button</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">The relevant details are given below</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/mKyC7jUO6RxPnSYlStRzMPhcrhVWAHsvE6vFpQeX.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="28">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Payment Summery</strong>&rdquo; (All entered payments are displayed here)</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/TGL73IdJIu5pETokdHnN4jxlXUWjnP3e8NFbGRve.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Enter Meters with Payments</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Go to pumper dashboard, Click payment</span></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="1" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Enter Meters&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/fuoJ79IjjwqedIok1Iykp4qJ7h13hiCwU9VY4oHU.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="2" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;New meter value&rdquo;</strong></span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Finalize&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/4UVSA5OIPheZC5DbvzO0qXFFdsRl79kr3WEaOgB7.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="4" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Payment method&rdquo;</strong>. Select the payment you want. If you select card, cheque and credit, enter other details related to it.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter <strong>&ldquo;Only cash amount&rdquo;</strong>. Fill the relevant form for other payment methods.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Amount correct? Click here&rdquo;</strong>. Only cash payment.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click <strong>&ldquo;Save&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/rOBdBIsoWR5eG99IDcQka0sSCtFaerdF4MXIrf6u.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">If you want to make another payment from the first entered meter, click <strong>&ldquo;YES&rdquo;</strong>. If there is no other payment, click <strong>&ldquo;NO&rdquo;</strong> (Image 8,9)</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/jMrTi3Oe9wK20903h8kGKxh7v3gblNMDRPs6Ma6g.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Add Other Sales</span></strong><strong><span style="font-size: 12.0pt; line-height: 107%; color: red;"> </span></strong></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="1" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Other sales</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/o4wuhlHVPwmPciE80SvbqF4ysrlO62AAfb3xvdVh.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="2" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Product</strong>&rdquo;</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Units and price are auto loaded.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Enter &ldquo;<strong>Qty</strong>&rdquo;(Quantity)</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Green color button</strong>&rdquo;</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Amount correct? Click here</strong>&rdquo; button</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Shows all entered other sale details.</span></li>
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Save or Save &amp; Print</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/sQzGsOfX8w71MpDlqRHJqFI0nt7ZdIihReyaJlLL.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="9" type="1">
<li style="margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>List other sales</strong>&rdquo; (Pumper dashboard).All other sales details are displayed here.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Close Pump</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">1.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Click <strong>&ldquo;Close Pump&rdquo;</strong></span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/wYe8lk1wx9PNX7meYOraqDoCcPXqB1VMQEHUKbKI.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">2.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Click <strong>&ldquo;Closing meter&rdquo;</strong></span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/16pqPpKyfNeDfpNIUp7LFgc7aGQSpdS2rdrer0xV.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">3.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Enter <strong>&ldquo;Testing Liters&rdquo;</strong></span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">4.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Enter <strong>&ldquo;Closing Meter&rdquo;</strong></span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">5.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Click <strong>&ldquo;Green color button&rdquo;</strong></span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">6.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Click <strong>&ldquo;Save button&rdquo;</strong></span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Pump Number, Sale Price, Starting meter and Total amounts are auto loaded</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/xwG46pU2hckETBdEQx3LVPaA3LzSHOq6J38ttmPn.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Close Shift</span></strong></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="1" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Close shift</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/azpNAjdzm98qTmkou3R9yTo7VEUyEzlUcHr3xXpf.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="2" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;">Check all payments and other details.</span></strong></li>
</ol>
<p style="text-indent: 0.25in; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click &ldquo;<strong>Balance to Operator</strong>&rdquo; button.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/JDxfGxFfKyRdrOr8AyWYF69vlcWaKEoYJq4CFHaT.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="3" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Close shift</strong>&rdquo; button.</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">The relevant details are displayed below. (Close shift)</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/BU98f6Ep0YnjEZZ0nBd1G74JfCenOIHcIAfmAm7d.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">After close shift, Create settlement</span></strong></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="1" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Go to Petro module</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Settlement</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cNcG54jbgDfickSyLNrqfu8sRspgb0Vy5ykC59Cz.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="3" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Pump Operator</strong>&rdquo;</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Transaction Date</strong>&rdquo;</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Work &amp; shift no</strong>&rdquo;</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Select &ldquo;<strong>Pump No</strong>&rdquo;</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%;">Starting meter, Closing meter, Sold qty, Unit price and Testing qty are auto load.</span></strong></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Add</strong>&rdquo; button.</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">The relevant details are displayed below.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/X8D2UlxpuZYaLvWqkzD7LYDCw2iOvACPfxRBRmNg.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="10" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Other sales</strong>&rdquo; button</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">After selecting the&nbsp; pump operator and shift number , related other sale details will auto loaded.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/8EMaiygpNKWGaufsEQsMpgDXglHKkO0RoCZoIHfo.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="12" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Payment</strong>&rdquo; button</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Click &ldquo;<strong>Payment finalized</strong>&rdquo; button</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/rV39VjttmBIUkhuMVk3XSTKF4pkrU8GpmkngzriJ.png" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="14" type="1">
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Check all related payment details.</span></li>
<li style="color: black; margin-top: 0in; margin-right: 0in; margin-bottom: 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Finally click &ldquo;<strong>Save</strong>&rdquo;.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/3tvUhXQi02EpvuFnoTdadYGUPRtEPEQ4YkkE16fb.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 10:52:47',
                'updated_at' => '2024-12-26 07:17:36',
                'deleted_at' => NULL,
            ),
            19 => 
            array (
                'id' => 20,
                'article_id' => 20,
                'title' => 'Add Journal',
                'content' => '<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 115%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 115%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: red; background: yellow;">Add Journal</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Go to Accounting Module</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Click <strong>&ldquo;List Journal&rdquo;</strong></span></li>
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/QfiFIt5Io9P4cEuOtKXLNN5oxOj5bCJujs5vulym.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Enter <strong>&ldquo;Date&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Location&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Add Note if needed.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Show in Ledger&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Account Types &amp; Accounts&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Debit &amp; Credit Amount&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Click <strong>&ldquo;Add&rdquo;</strong> button.</span></li>
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Finally click <strong>&ldquo;Submit&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/XPqAN5MVSRm9VW7gkVHKS6RhDzafqbmSaNgzr13l.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-09 12:34:00',
                'updated_at' => '2024-12-26 07:20:02',
                'deleted_at' => NULL,
            ),
            20 => 
            array (
                'id' => 21,
                'article_id' => 21,
                'title' => 'Checking Product Stocks',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 115%; color: red; background: yellow;">Why Stocks &amp; Stock values are minus?</span></p>
<p style="margin: 0in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 115%; color: #006600;">If sales more than purchases, qty and value show as minus.</span></p>
<p style="margin: 0in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: red;">How to check the details</span></strong><span style="font-size: 12.0pt; line-height: 115%; color: red;">?</span></p>
<p style="margin: 0in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ul style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Go to Product Module / List Product</li>
</ul>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/HgadzYn4f6qIDGAlyAvR4xKHW9TUxO0MMW1hJCKf.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 24px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the item click <strong>&ldquo;Action&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 24px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Go to <strong>&ldquo;Product Stock History&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 24px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Check the transaction</li>
</ol>
<p style="margin: 0in 0in 10pt 1in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">(Opening Balance, Sales, Purchases)</p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/eUcoIEFnAG6egIt3m9QaPFWOnubLjWZ6dKGupS89.png" /></p>
<ul style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Check all the details are coerrct.</li>
</ul>',
                'language' => 'en',
                'created_at' => '2024-12-09 12:39:16',
                'updated_at' => '2024-12-26 07:20:48',
                'deleted_at' => NULL,
            ),
            21 => 
            array (
                'id' => 22,
                'article_id' => 22,
                'title' => 'Add Fleet Settings',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Fleet Management&rdquo;</strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Fleet Setting&rdquo;</strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Nl9Y2uq5hY55vuB9e5v3K3EXMA6VsOsKL0SnGujb.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Trip Categories&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> Button</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/7Jm49rgEaoIYFjC43BtE9lbKk8ONgvMzErjkIuGX.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Check if the date is correct</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Trip Category Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Amount Calculate Method&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save Button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/V66ggOTk3JMh23vkPiRXBuCD9IFsrRhqBPpaYzr3.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="7">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Fuel Types&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add Button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/dybVPm11VYtOzPw8IisAk9qliitDeMFQkluqdUbu.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="9">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Fuel Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Current Price&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9PJSEdrzfCxjcdjyWJMnWwjh86aJRawKEKuthdJK.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="13">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Fleet Logos&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/FkNzro0pn31y2PjQsCnXJPTIHCpcxBdiYxKFBzYB.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="15">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Image Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Alignment&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Add Image&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/JSBSNBuYrVH8kIZTG44TVvBCK8r9av3EPZ5rAZ4l.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="19">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Original Locations&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/3yV4ZxYe8232wBdhESjNMQKV4yJXougDNscRCbE2.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="21">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><a name="_Hlk180921591"></a>Check if the date is correct</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Name&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/EVHiQQgb91daCoZXuLa4gGGa6ue4y77XLb89R5sq.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="24">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Account Numbers&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/8EgkYpZ26yrwBS9B88HUtmGx9IJe3SbOrFTYPULh.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="26">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Invoice Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Delivered to Account No&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Account Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Dealer Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Dealer Account Number (Bank)&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Bank Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Branch&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Rxd39K6R8fNGHthcSrfgletSznEuqSZpPhXORRt0.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="34">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Product&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/EygHGoqmk7u4fQS8NGQNaIzldh7jP0cXncmbAMge.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="36">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Check if the date is correct</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Auto load details</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/M6mzKmHWpwYkWcmPyc0j464YCOqEapSmeA3WtQvX.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="41">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Starting Invoice Number&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Qh10e1zhUMae35EsAceCbR21vDQ6oOP9rMwDWGap.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="43">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Check if the date is correct</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Prefix&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Starting Number&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save Button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9QqTvDBI4rBdRVJuffF6CZLzFh4wgOAHbrIBvV4Z.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="47">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Helpers&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/elV15J23dL1TWCjbT7gPbprbwT0m9fFm8gwD0SlG.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="49">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Check if the date is correct</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Employee No&rdquo;</strong> Auto Load</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter<strong>&rdquo; Helper Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;NIC Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Salary expenses category&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Advance Expenses category&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/YFvuPn4eSAAZKLUcR3o402fWQIUwUHOlOg37Pile.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="56">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Drivers&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/lP1iTAB5RXXgVv32hc8zjFjIdbt7P52jD8dqsfXB.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="58">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Check if the date is correct</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Employee No&rdquo;</strong> Auto Load</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Driver Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;NIC Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;DL Number&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;DL Type&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/VdUV0MPJ9UOgVrZNlUow3OKBZqQ0jSqijXKDaU01.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="64">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Expiry Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Salary Expenses Category&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Advance Expenses Category&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/KlX125BRptE0AmWx7fTNph591HcUemEumi6BtphJ.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="68">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Trips&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/o2BaEABgeo26zhMg4RJ7QPkF1Hdr7Z8w8WS0ZZrk.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="70">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Check if the date is correct</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Trip Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Trip Category&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Delivered to Account No&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Original Location&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Destination&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Distance&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Actual Distance&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Rate&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Auto Load tip Amount</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Driver Incentive&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Helper Incentive&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9IjxNJS6Q6YlQmdTywxXD6Ik0TB0vRUmd2zuypDc.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="82">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Driver Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Applicable to&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Incentive Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Fixed Amount&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add button&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/0QTYqpTymCD7lHMmN1cyUkb6ZqT5qgUEQ3NHOAMh.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-10 08:26:52',
                'updated_at' => '2024-12-10 08:26:52',
                'deleted_at' => NULL,
            ),
            22 => 
            array (
                'id' => 23,
                'article_id' => 23,
                'title' => 'Add New Fleet & Check List Fleet',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Management</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click List Fleet</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/AUtKbyzDSIPbvKaL4SJqwteqSOWnEzOzqhMMDMqw.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: red; background: yellow;">How to add New Fleets</span></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Add" </strong>Button</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/PhPMiwkYvrXrJG5sfnob1IRWfySgAqNPGLYfBaFk.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Code for Vehicle&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Location&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Vehicle Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Vehicle Brand&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Vehicle Model&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter<strong> &ldquo;Chassis Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Engine number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Battery Details&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Tyer Details&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Opening Balance&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Income Account&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Starting Meter&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Fuel Type&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally Click <strong>&ldquo;Save&rdquo;</strong> Button</li>
</ol>
<p style="line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 0in 0in 8pt 0px;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tgEs0VkVYvMEoqCg8l2CBNEn5UxumtrysgRWCBeT.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-10 10:10:05',
                'updated_at' => '2024-12-26 07:22:41',
                'deleted_at' => NULL,
            ),
            23 => 
            array (
                'id' => 24,
                'article_id' => 24,
                'title' => 'Add New Trip Operation',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Management</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Add Trip Operations</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/4mffoPXbEkKT5bq7Xq8Ny6FQCNSg0o9Zp6w2dGoQ.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date of Operation&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Trip Operation Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Location&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Customer&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Customer VAT Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Invoice No&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Order Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Trip Categories&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Trip&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Delivered to Account Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Product&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Quantity&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;driver&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Helper&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Rate (per km)&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Distance&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Starting Meter&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Ending meter&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Amount&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Driver Incentive&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Helper Incentive&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;VAT Invoice&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/rNObYoAUdoK1OJ5QlRwzEkbZYa1N637NvNx6Loth.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="27">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Amount&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Payment Method&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;payment details&rdquo;</strong> if required</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;save&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/FtXQC4qpFTHod3Ec9YGeY3Vh0hVgNLSFLX1q8JvG.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-10 10:19:41',
                'updated_at' => '2024-12-10 10:19:41',
                'deleted_at' => NULL,
            ),
            24 => 
            array (
                'id' => 25,
                'article_id' => 25,
                'title' => 'Check List Trip Operations',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Management</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click List Trip Operations</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/hxT5UiFOkQP96thYxXLYFFSGU9ZOMozqwAxlPs9q.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Location&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Customer&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Trip&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Driver&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Helper&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Payment status&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Payment method&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> Button (Add New Trip Operation. Please check add trip operation module)</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/sJT1UH6L042nbWvDHrY7ESxRRHTpIRoiKKjqfB58.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-10 10:23:25',
                'updated_at' => '2024-12-10 10:23:25',
                'deleted_at' => NULL,
            ),
            25 => 
            array (
                'id' => 26,
                'article_id' => 26,
                'title' => 'Add Fleet Invoices and List Fleet Invoices',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Management Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Invoice</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/sNEucK9CuU1oG6oCe8OC2EZiDAGYXK2aLlnqWEZl.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Create Invoice&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Location&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Trip Category&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Customer&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Invoice Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Original Location Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Original Location&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Logo&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save Invoice&rdquo;</strong> Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/WOZmh8EwDN1FZz7qoVjCXqXgVBkegjgBdFNg9TUJ.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="14">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>List Invoices</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Invoice Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">You can choose by Search bar</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/idU9W3ZKDd4HxznYyGfWEfIpa2FFub26yAceQSLq.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 04:31:58',
                'updated_at' => '2024-12-11 04:31:58',
                'deleted_at' => NULL,
            ),
            26 => 
            array (
                'id' => 27,
                'article_id' => 27,
                'title' => 'Fuel Management',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Management</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fuel Management</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/SjzPQlrBt17VnYsiGgpRMHbdsIXuRBucGiTPvfef.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Driver&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">You can choose by search bar</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/U3Uajoy53qGGdoST9g1sxaAEZwd9BsUG81kqa5CS.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 06:02:42',
                'updated_at' => '2024-12-11 06:02:42',
                'deleted_at' => NULL,
            ),
            27 => 
            array (
                'id' => 28,
                'article_id' => 28,
                'title' => 'Milage Changes',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Milage Changes</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/JvEfbhVELRsXvoKDRAngvZe8Q7WYoedNWSOHbdpL.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Driver&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Helper&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Milage Status&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Trip Operations&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Trips&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/1WumN0s95n0BSo07oAZbB604xJpozW9Kn8qxERgV.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 06:06:40',
                'updated_at' => '2024-12-11 06:06:40',
                'deleted_at' => NULL,
            ),
            28 => 
            array (
                'id' => 29,
                'article_id' => 29,
                'title' => 'Check Fleet Profit & Loss',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Management Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Fleet Profit &amp; Loss</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/pc47Pag9VvDo0veadVYtl31FLHhyVpyy6t5iDego.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Ref No&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Type&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/RSdVTP4bIuu6PEtK0IJOsEnSuzJbSTmNDIZRaJ8X.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 06:11:30',
                'updated_at' => '2024-12-11 06:11:30',
                'deleted_at' => NULL,
            ),
            29 => 
            array (
                'id' => 30,
                'article_id' => 30,
                'title' => 'List Members & Add New Members',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Click Member Module</li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Click List Member</li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/uC4JNk9K9i5ZuARP8TUEJRMwJ4qoSpeQ2zIOsXOa.png" /></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date of Birth&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Province&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;District&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Electrorate&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Gramasevaka Area&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Gender&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Member Group&rdquo;</strong></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/rmCh5auqmyLWkF5vpghl8yCal593qA6QDtMNG7Pc.png" /></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; color: red; background: yellow;">How to add New Member</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="11">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> Button</li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/LyEt8yARGVfgLCOAGFURd5Budz4b8IXpNxWM9LGh.png" /></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Member Code&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Name&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Address&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Electrorate&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Gramasevaka Area&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number 1&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number 2&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number 3&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Finally Click <strong>&ldquo;Save&rdquo;</strong> Button</li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 8pt 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/BiC7ApIsP9Lg6j5moXPWcnmzcQvQrzQgj4crLm4S.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 06:18:50',
                'updated_at' => '2024-12-11 06:18:50',
                'deleted_at' => NULL,
            ),
            30 => 
            array (
                'id' => 31,
                'article_id' => 31,
                'title' => 'Check List Suggestions & Add New Suggestions',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Member Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click List Suggestions</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/kGw9wfdqsJbNW2dEDkqhdR9wimILrBcTreUssYJg.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Balamandala Area&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Main Areas&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Name of Suggestions&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Details of Suggestions&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Is <strong>&ldquo;common problem&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Area which Involved&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;State of urgency&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Solution Given&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/qU5igFlVzMTaEtFRcCNbBvKmUszzF7jpnFCIq04f.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">How to add New Suggestions</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/8anrOlwZkLv6x7n8xb5UbKCI1yVlTp6jO2vyuttF.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="13">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Member&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Balamandalaya&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Service Area&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Heading&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Edit <strong>&ldquo;Heading&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/rBckaHZYwYCGfxEbsKWkJQYCp0rrpEiIplwsBPCm.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="19">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Is common problem&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Area Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;State of Urgency&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Choose file Upload <strong>&ldquo;Document&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Remarks&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/NK41bm1xGzqeg9LFScbSFQ9Lry84DZEfsqIAtG4B.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 08:01:45',
                'updated_at' => '2024-12-11 08:01:45',
                'deleted_at' => NULL,
            ),
            31 => 
            array (
                'id' => 32,
                'article_id' => 32,
                'title' => 'Add Agent',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click the Three Strips</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Shipping Module</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/woz5nd9z57MwYK30ne2dZKWEWoO8myLZeAVxvhmj.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Agent&rdquo;</strong></li>
</ol>
<p style="line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 0in 0in 0in 0px;">&nbsp;</p>
<p style="line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 0in 0in 0in 0px;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/WBedFRabmmCueiLZtdiAwd6MOslDAO1FecFOsh0O.png" /></strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add Button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/lYQh466Yc5aAmFjsj5dntO8ZdxXuNCfI754M90WD.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Details <strong>(Date, Name, Address, Mobile No 1, Mobile No 2, Land Number , Opening Balance)</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save&rdquo; </strong>Button</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tgbZPN3OeEwyXrBUaAe2bDk45yC1AXXLerCPoQHK.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 08:13:23',
                'updated_at' => '2024-12-11 08:13:23',
                'deleted_at' => NULL,
            ),
            32 => 
            array (
                'id' => 33,
                'article_id' => 33,
                'title' => 'Add Partner',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Shipping Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Partner&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/B5kVGH01l3STOmiMhIT0ST3CIVRQ6vIzlceKOfZH.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ZnvAh5xdElDoX4b8C9BJ5CnxltXBtrpl83hNycW1.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add below details</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Added <strong>&ldquo;date&rdquo;</strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Name&rdquo;</strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Address&rdquo;</strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Mobile No 01&rdquo;</strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Mobile No 02&rdquo;</strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Land Number&rdquo;</strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Opening balance&rdquo;</strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tMxRjBo7o0YmOsxjNZ6ejnaUEyfrNBCWyLQdK3YY.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 08:19:14',
                'updated_at' => '2024-12-11 08:19:14',
                'deleted_at' => NULL,
            ),
            33 => 
            array (
                'id' => 34,
                'article_id' => 34,
                'title' => 'Add Recepiant',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Shipping Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Recipients</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/X3EyPUU7NNNMLW4kcXyv0eGB1CcUfgKnGyUuZbM5.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add Button&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/6CpYU2HhbJZ5c8KpMtmmoNFrIsV3BjFpwkqUxDsY.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Details <strong>(Added Date, Name, Address, Mobile No 1, Mobile No 2, Land Number, Postal Code, Landmarks)</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/BTyE8wRvmh3c6CWsuJtAvevgURQuC1ynMCtkB4P1.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 09:47:47',
                'updated_at' => '2024-12-11 09:47:47',
                'deleted_at' => NULL,
            ),
            34 => 
            array (
                'id' => 35,
                'article_id' => 35,
                'title' => 'Add Shipment SW',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Shipping Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Add Shipment SW</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/l4O463ddDSsggo1JgvyzGoAveIYzo4zzAmpVk0T2.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Date"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Select Location"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>"Shipping Agent"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Sender or Customer"</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">How to add new Sender or Customer</p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-alpha; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Sender/customer (Plus icon)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Contact Type</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter Name</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Need to send SMS</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Credit Notification Type</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Contact ID</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter Tax Number</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter VAT No</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter Opening Balance</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Pay Term</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Customer Group</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter Credit Limit</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter Password</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Re- enter Confirm Password</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Transaction Date</li>
</ol>
</li>
</ol>
</li>
</ol>
<p style="margin: 0in 0in 0in 0.75in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.75in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ZOUQ8c3WSEdriXw5at1bwKc4bm8KHKLc6hSK4VIu.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-alpha; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-roman; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;" start="16">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;Select <strong>"Email"</strong></li>
</ol>
</li>
</ol>
</li>
</ol>
</li>
</ol>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-alpha; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-roman; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;" start="17">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Mobile"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Alternate Contact Number"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Landline"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Assigned to"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Vehicle Number"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Address"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Address Line 2"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Address Line 3"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"city"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"State"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Country"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Landmark"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Passport/NIC No"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Passport/ NIC Image"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Choose <strong>"Signature File"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>"save"</strong> button</li>
</ol>
</li>
</ol>
</li>
</ol>
</li>
</ol>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 24px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-alpha; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-roman; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;" start="32">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;" start="7">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Address <a name="_Hlk176258456"></a>(After selecting the customer, the relevant address is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Mobile (After selecting the customer, the relevant mobile is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Recipient</li>
</ol>
</li>
</ol>
</li>
</ol>
</li>
</ol>
</li>
</ol>
<p style="margin: 0in 0in 0in 1.25in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">How to add new recipient</p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 48px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-alpha; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="list-style-type: lower-roman; margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Added Date</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Name</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Address</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Mobile No 1</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Mobile No 2</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Land Number</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Postal Code</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Landmark</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click save button</li>
</ol>
</li>
</ol>
</li>
</ol>
</li>
</ol>
</li>
</ol>
</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/baQvtPIuNRAMn6mfFiER6tXhBaAeyFpckW1Wlwdc.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="10">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Address"</strong> (After selecting the customer, the relevant address is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Mobile No 1"</strong> (After selecting the customer, the relevant Mobile No 1 is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Mobile No 2" </strong>(After selecting the customer, the relevant Mobile No 2 is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Postal Code"</strong> (After selecting the customer, the relevant Postal Code is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Land Number"</strong> (After selecting the customer, the relevant Land Number is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Landmark"</strong> (After selecting the customer, the relevant Landmark is entered here)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Shipping Mode"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Type of Package"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Schedule for delivery"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Delivery Date"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Shipping Partner"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Shipping Status"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Driver"</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.25in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ZOUQ8c3WSEdriXw5at1bwKc4bm8KHKLc6hSK4VIu.png" /></p>
<p style="margin: 0in 0in 0in 0.25in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="22">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;" start="23">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Upload <strong>"Package Image"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Package Name"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Price Type"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Length in cm"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Width in cm"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Height in cm"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Weight in kg"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Price per kg"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Volumetric Weight"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Shipping charge"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Declared Value"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"service Price"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Auto load Total</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Add" </strong>Button</li>
</ol>
</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/hkoPSBGR2kBbLfnKXtWgAFvTdcxyEp03UUFdDLTz.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="22">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;" start="36">
<li style="list-style: none; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">
<ol style="margin-bottom: 0in; margin-top: 0px;" start="36">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">The information entered above is shown here</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Total Amont"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>"Payment method"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Payment Note (Enter if required)</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally <strong>"Save or Save &amp; Print"</strong></li>
</ol>
</li>
</ol>
</li>
</ol>
<p style="margin: 0in 0in 8pt 0.75in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/GJ8HgDDerHqFMUVom4Xb4PBUOMUkG2zmS4zzs9Fd.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 10:04:18',
                'updated_at' => '2024-12-26 07:37:33',
                'deleted_at' => NULL,
            ),
            35 => 
            array (
                'id' => 36,
                'article_id' => 36,
                'title' => 'List Shipment',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click shipping module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click List shipment</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/jArcMc9RPbuiUK1CuJgGbF2DIJxAx5SDXcjdSybP.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Below is the information of all the shipment entered so far. You can select the required information from it.</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/AK4GjbkHftgovayousptbXIokbEviIatO2HneiKf.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-11 11:09:45',
                'updated_at' => '2024-12-11 11:09:45',
                'deleted_at' => NULL,
            ),
            36 => 
            array (
                'id' => 37,
                'article_id' => 37,
                'title' => 'Shipping Module Settings',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Shipping Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Shipping Settings</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/vbBuIQsLUahllTVYT3JjVKCeU4j1CsOriTx9iy8n.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Drivers"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Add Button"</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/GbW8gsDQgHLMPKaoNsrIql4Z3GlGx6IYLuoJc7X6.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Joined Date"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"HRM Enable Button"</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/DQ1Q9lZ64qhnaug6Mh41txvyQbPcgg3auEKAaNEJ.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="7">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Employee No"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Driver Name"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"ID Number"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"DL Number"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally Click <strong>"Save" </strong>Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/G3KbWulSQigNd2AcfJURc6uwhd7gf1VKM7uTNxn6.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Collection Officer"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Add Button"</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/nBXlIiFlfD2jKS7pU5csBDkO4b9NX5pZLl6U54Xe.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="14">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Joined Date"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"HRM Enable"</strong> Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/0LpBpVX8R6vvYcTqGm25PuD4xL25MH7bgCDxKaXy.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="16">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Employee No"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"Collection Officer"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>"ID Number"</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Save" </strong>Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/NMm8y4CpMjOw1TBph1RigOTty6GcKMK812QrKqxu.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="20">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Shipping Types&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/zy6cLxLA9eGpHzn9QGn0JBhJdv7iEbXFN2Pe5Me3.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="22">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Shipping Types&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click<strong> &ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/SWsGwzCHbZ19UKjRvyTsMlv6qjMNU9aSipkW7qiB.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="25">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Shipping Status&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/agqJ3FittwKGCDXIowG3Cnt1DzQupBaHsZwL3NFK.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="27">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Shipping Status&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/coYAKnz3O4wI6LNOhOpaZu3fAxVxAc7PfFRyboYG.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="30">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Shipping Mode&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/K13RNuLmxkXaWLLN4CjOeUV8dZ9u7gqshVHFMNni.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="32">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Shipping Mode&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/NLofqBlGXon4SQgkaRU7uRQk0mYE8NFBkVe8lxR1.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="35">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Schedule for Delivery&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/dvJzp2l174KHWaGgLpedepfKCPnfUEwITtFF6vte.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="37">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Schedule for Delivery&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/IAMhUst5T07ajimneTmxfkQPStM2Ea505UkKAY5C.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="40">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Delivery Time in Days&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/PIQ4kP89UQan4zgOO6A7aC2vKBDRxQQwdqRg5OY6.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="42">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Days&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/zFkiPlplgY5FpI3MIYx8VUQ481kH2y38OYTHFwTa.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="45">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Prefix&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/lOTbQzmPRWreVMQBBKAlQB34484qdO31WIsjAiF0.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="47">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Tracking ID Starting No&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Shipping Mode&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>Enter &ldquo;Prefix&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click<strong> &ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/NXFPYQqRPGU7nSbICNg1eG9MbgOjhAaEDs4m5pZ5.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="52">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Package&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/KUjLmHY68OEzaCD494JorlohahF1LwOGKXPMx1BJ.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="54">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select<strong> &ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Package Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Package Details&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/3gNk6rdMYzrwxy8B9rRWzRv211E7nokTisCJS5qd.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="58">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Shipping Price&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Imitrdh4kosf82jkGLykzt3eQ9aBXqpp3uePrGQe.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="60">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Package&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Shipping Partner&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Shipping Mode&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Constant Value&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Price&rdquo; &amp; Enter &ldquo;Price&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/aB8IEX2p4jkdvCM7orBOn6oEyeeVEo2qD8vlAraV.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="67">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Credit Days&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/kFuUJsdZ7EyyIsDGcbdt7K4vlEPIW61sEONKZjIn.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="69">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Added Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Credit Days&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/HqiFRU2ozZM76mO8L7gctQimF3BvInmQy2gUZtni.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="72">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Shipping Account&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/YwmwOdG6EVwDCAW71QUZpCLyww0VNZArBCFkpk9S.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="73">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Expense&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Income&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Shipping Mode&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Shipping Partner&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/RkFf5iF1HCZH4dCFU0CLYM6gmwlR2kKUxMzsrw1K.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="78">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Shipping Dimensions&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/xhtqoaBTd08Qw56rRtRiektvolK21puakMathnch.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="80">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Shipping Invoice Logo&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9dEeDnAqjUtxDPAs1uHU5yiPRQUXwgcWSXrslt9d.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="82">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Image Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Alignment&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Image&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo; </strong>button.</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/rMfF5Y1izifJ5vNJmS7LnKEcMd094POpLYU4fzQP.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-13 08:47:43',
                'updated_at' => '2024-12-26 07:49:11',
                'deleted_at' => NULL,
            ),
            37 => 
            array (
                'id' => 38,
                'article_id' => 38,
                'title' => 'How to get Backup',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: red;">Is it necessary to get a backup from the system?</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #00b050;">It is essential to make a backup of the software after every action you take. This will solve many issues. </span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #00b050;">This is very important.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #00b050;">If you make a mistake while working on the software, it is easy to reinstall the previous backup to the </span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #00b050;">system and correct the error again.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #00b050;">Also, this database is exclusive for your business, so sytem speed will be better than before.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: red;">How to get a backup from the system?</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: red;">Follow the instructions below.</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #002060;">Go to Backup Module</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #002060;">Click <strong>&ldquo;Create a New Backup&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/YwQQeUIortB97GMLgj2ITpIESNWWRSw4MUMVKRTj.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #002060;">Click the <strong>"Green Download"</strong> button to download this backup to your computer.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #002060;">Click the <strong>"Red Delete"</strong> button only if you have an unwanted backup.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Note:</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: #002060;">All the backups older than recent 3 backups, will auto delete from the system. As such, please save each and every backup of your system in your computer / hard Disk.</span></p>',
                'language' => 'en',
                'created_at' => '2024-12-13 13:51:02',
                'updated_at' => '2024-12-26 07:50:29',
                'deleted_at' => NULL,
            ),
            38 => 
            array (
                'id' => 39,
                'article_id' => 39,
                'title' => 'Customer Payment',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Contact Module</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Customer&rdquo;</strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/DHERrv8mnLhRmfvJ4hhWpkiskXJQGSA76jM60Be0.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Search your Customer Name</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Go to <strong>&ldquo;Action&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Pay Due Amount&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/bAfQ5DgtBI3YAjXcJ4uI3v9eEQiRDicwPec9LRj4.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Amount&rdquo;</strong> paid by customer.</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Payment Method&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Accounting Module&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Payment Note&rdquo; </strong>if needed.</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/P3zCMQ3rlUm34klTFco8TdZaqg27zIbUXRrzk8FJ.png" /></p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-14 05:24:03',
                'updated_at' => '2024-12-26 07:51:45',
                'deleted_at' => NULL,
            ),
            39 => 
            array (
                'id' => 40,
                'article_id' => 40,
                'title' => 'How to get Customer Statement',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">How to get Customer Statements</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Go to Contact Module</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Click Customer Statements</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/eQflH2FTwlxLufsiARr73r3ek9jBPfNfCCmFKVbU.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Business Locati</strong>on&rdquo;</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Customer&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Date Range&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Customer State</strong>ment Logo&rdquo;</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Finally Click <strong>&ldquo;Save&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/KuM4xdVfCNunDjbsvoxPWbE7ukK5Uk5kMB7z276P.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">How to Add Customer Statements Logo</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select Statement Settings</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Click <strong>&ldquo;Add&rdquo; </strong>button</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/GkusjfdpV8wHWZJWH32OeJkXsdeGxVs6PDCvtVBp.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Enter <strong>&ldquo;Image Name&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Alignment&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Add <strong>&ldquo;Note&rdquo;</strong> if needed.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Select <strong>&ldquo;Text Position&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Click <strong>&ldquo;Choose File&rdquo;</strong> Add Image.</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="color: black;">Finally click <strong>&ldquo;Save&rdquo;</strong> button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/L0DRJXhKGSDVAMchC74FuwhvpWc5ZEGQsx2bkpVY.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-14 08:01:18',
                'updated_at' => '2024-12-26 07:52:58',
                'deleted_at' => NULL,
            ),
            40 => 
            array (
                'id' => 41,
                'article_id' => 41,
                'title' => 'Day End Settlement',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: red;">Follow the steps below after completing all the settlements of the pumps you enter into the system.</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Petro Module</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Day End &ndash; Settlements</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Add&rdquo;</strong> button</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/wqmUP87YbKcPAwb1lVQJ5oSjx0J973R3UiR2UKrQ.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Select <strong>&ldquo;Day End Date&rdquo;</strong></span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Tick <strong>&ldquo;No Operations&rdquo;</strong></span> <span style="font-size: 12.0pt; line-height: 107%; color: black;">(Reconfirm the pumps by clicking the boxes against the pump for was not in operation for the selected Date).</span></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Save&rdquo;</strong> button</span></li>
</ol>
<p style="line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 0in 0in 8pt 0px;"><span style="font-size: 12.0pt; line-height: 107%; color: black;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/oiqzxKmlOQku9W5otlOhXJMbS4ebBsIM6ZF3uKh7.png" /></span></p>',
                'language' => 'en',
                'created_at' => '2024-12-14 08:40:54',
                'updated_at' => '2024-12-26 07:53:36',
                'deleted_at' => NULL,
            ),
            41 => 
            array (
                'id' => 42,
                'article_id' => 42,
            'title' => 'Add Dip (Dip Management)',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, </span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Dip Management </span></strong></li>
</ol>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click&nbsp;Petro</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Dip Management </span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/h6iY8dRccCWjyaGjTIe8P386PyPBh4uJVN5Pekjo.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="3">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">&nbsp;Click <strong>&ldquo;Add Dip&rdquo;</strong> to add the Dip Reading. (Date should be Dip Taken Day)</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the <strong>&ldquo;Tank&rdquo;</strong> (Add Tank Dip Reading Tank Wise Separately One by One)</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add <strong>&ldquo;Dip Reading&rdquo; </strong>(Tank Measured Dip Stick Number)</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add <strong>&ldquo;Tank Balance&rdquo;</strong> in Liters (Based on Dip Reading)</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Auto Load Current Quantity</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click &ldquo;Add&rdquo; button</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click <strong>&ldquo;Save&rdquo;&nbsp;</strong></span></li>
</ol>
<p style="line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 6pt 0in 8pt 72px;"><span style="font-size: 12.0pt; line-height: 150%;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/JcoroZeDBqVzYnc0YxdG7rUl3LC1ZQ5eOaBLdoco.png" /></strong></span></p>',
                'language' => 'en',
                'created_at' => '2024-12-14 09:21:03',
                'updated_at' => '2024-12-14 09:21:03',
                'deleted_at' => NULL,
            ),
            42 => 
            array (
                'id' => 43,
                'article_id' => 43,
                'title' => 'Dip Reseting',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, </span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Dip Management&nbsp;</span></strong><strong><span style="font-size: 1.0pt; line-height: 150%; background: yellow;">&nbsp;</span></strong></li>
</ol>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Petro</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Dip Management</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Dip Resetting</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/0Nx9Anp5psB3sb1DyJGyV8osCCXQauRbCGhlnZEs.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click &ldquo;Add Resetting Dip&rdquo;</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the &ldquo;Transaction date&rdquo;</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the &ldquo;Location&rdquo;</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select &ldquo;Tank&rdquo;</li>
</ol>
<p style="text-indent: 0.5in; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Product, Current qty will load auto.</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="8">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add &ldquo;Reset New Dip box&rdquo; <span style="color: #c00000;">Add current physical stock to the related tank.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select related product &ldquo;Inventory Adjustment Account&rdquo; in the dropdown list.</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add the &ldquo;Reason&rdquo;</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Save</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cNHEiPwJtTGSGHBraiXy8xT8FT3sgp58QioWU4Yj.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>',
                'language' => 'en',
                'created_at' => '2024-12-14 09:47:49',
                'updated_at' => '2024-12-14 09:47:49',
                'deleted_at' => NULL,
            ),
            43 => 
            array (
                'id' => 44,
                'article_id' => 44,
                'title' => 'How to Create Employee Ledger Account',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, </span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; color: red; background: yellow;">Create an &ldquo;Employee Ledger Account&rdquo;</span></strong></p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt;">&nbsp;</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">Go to Accounting Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">List Account</li>
<li style="margin: 0in 0in 0in 0px; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">Click Add</li>
</ol>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/CipQCUcRT9ZG537tUEQ5Fdx9Zhf0slgt16fk7tXj.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Location&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;AC Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Account Type: Current Liabilities&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Show in Balance Sheet: <strong>&ldquo;Yes&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Account Group: Employees&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Account Number <strong>&ldquo;Automatically creates&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally ckick <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/nEn1tl3Inq1MtoCpOMNhHOPl555MYbAPurX0mgz7.png" /></p>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-14 16:56:26',
                'updated_at' => '2024-12-14 16:56:26',
                'deleted_at' => NULL,
            ),
            44 => 
            array (
                'id' => 45,
                'article_id' => 45,
                'title' => 'Add Expenses Category',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 6pt 0in 0in 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Add Expenses Category&nbsp; </span></strong></p>
<p style="margin: 6pt 0in 8pt 0.75in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 1.0pt; line-height: 150%; background: yellow;">&nbsp;</span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 48px; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt;">Click Expenses Module</span></li>
<li style="margin: 6pt 0in 8pt 48px; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt;">Expenses Categories</span></li>
<li style="margin: 6pt 0in 8pt 48px; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt;">Click </span><strong><span style="font-size: 12.0pt;">&ldquo;Add&rdquo;</span></strong><span style="font-size: 12.0pt;"> button</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ZwjPY8G6Se4Yd4lWPAhLTTaoMzSQMoW2gdeSBIRL.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="4">
<li style="margin: 6pt 0in 8pt 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add <strong>&ldquo;Category Name&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add <strong>&ldquo;Category Code&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> &ldquo;Payee&rdquo; </span></strong><span style="font-size: 12.0pt; line-height: 150%;">Cheque Module Not Enabled.</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> </span></strong></li>
<li style="margin: 6pt 0in 8pt 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the Related Payment Type &ldquo;Accounting Module&rdquo; </span></li>
<li style="margin: 6pt 0in 8pt 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click &ldquo;<strong>Save</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/mH8NjDZJLcIxOKIzKF1sc06mNmZG7HtcOd8hOHYL.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-15 04:13:21',
                'updated_at' => '2024-12-15 04:13:21',
                'deleted_at' => NULL,
            ),
            45 => 
            array (
                'id' => 46,
                'article_id' => 46,
                'title' => 'Loan Given',
                'content' => '<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 115%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 115%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: red; background: yellow;">Loan Given</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Accounting Module&nbsp;</li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">List Account</li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the created <strong>"Loan AC"</strong></li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>"Transfer"</strong> Button</li>
</ol>
<p style="margin: 0in; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/JDKoXg4ak6DOBojT8XRVzOeAqepa2s2wJPuk20PP.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">AC Group: Select <strong>"Loans Given"</strong></li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Transfer To account (Loan Given Account)</li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Amount&rdquo;</strong>: Enter given amount</li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Cheque No&rdquo;</strong>: (Optional)</li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Tick<strong> &ldquo;Transfer&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Note&rdquo;</strong>: Optional</li>
<li style="margin: 0in 0in 0in 0px; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally Click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/WPLyWKaB9Z1aALtMF3v26U0P3Hodp8dtEXNf9T2w.png" /></p>
<p style="margin: 0in; text-align: justify; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/c2vF0piHLiSt9r9LwgkmnoZxLDPSoyaq2T1UE9uk.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-15 05:44:35',
                'updated_at' => '2024-12-15 05:44:35',
                'deleted_at' => NULL,
            ),
            46 => 
            array (
                'id' => 47,
                'article_id' => 47,
                'title' => 'Price Change Effect for Profit & Loss',
                'content' => '<p style="margin: 0in 0in 10pt; text-align: center; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 115%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 10pt; text-align: center; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 115%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 10pt; text-align: center; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; color: red;">To effectively manage the Profit &amp; Loss when there is a price change; please follow the procedure mentioned below:</span></p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="1" type="1">
<li style="margin-bottom: 0in; margin-top: 0in; line-height: normal; margin-right: 0in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt;">Do not enter the stock purchased at the new price into the system until the remaining stock on hand is exhausted.</span></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="2" type="1">
<li style="margin-bottom: 0in; margin-top: 0in; line-height: normal; margin-right: 0in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt;">Once the old stock runs out, you can include the new stock in the system.</span></li>
</ol>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.25in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt;">If you don\'t follow this arrangement, it may lead to significant changes in Profit &amp; Loss.</span></p>
<p style="margin: 0in 0in 0in 0.25in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.25in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt;">When changing the price, if there is any remaining stock, please do not change the buy price until that stock is exhausted. Only change the selling price.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-15 05:56:52',
                'updated_at' => '2024-12-15 05:56:52',
                'deleted_at' => NULL,
            ),
            47 => 
            array (
                'id' => 48,
                'article_id' => 48,
                'title' => 'Add Supplier Map Products',
                'content' => '<p style="margin: 0in 0in 10pt; text-align: center; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 115%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 10pt; text-align: center; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 115%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 10pt; text-align: center; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Add Supplier Map Product</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Go to Contact Module</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Add Supplier Map Products</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/17JexxA9TF8SyiUJXekTk3Ep0LJpjPzaXDmCsE8M.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Supplier&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Search Item or Select Unmapped Product</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Mapped&rdquo;</strong> button.</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/qHhQWbByqUenrdP6LIQCXadl41CMuxk3g47c1ZNk.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-15 06:28:53',
                'updated_at' => '2024-12-15 06:28:53',
                'deleted_at' => NULL,
            ),
            48 => 
            array (
                'id' => 49,
                'article_id' => 49,
                'title' => 'Add Stock Adjustment',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 6pt 0in 0in 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Stock Adjustment &nbsp;</span></strong></p>
<p style="margin: 6pt 0in 0in 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 3.0pt; line-height: 150%; background: yellow;">&nbsp;</span></strong><strong><span style="font-size: 1.0pt; line-height: 150%; background: yellow;">&nbsp;</span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click Add Stock Adjustment Module</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Add Stock Adjustment </span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/sL6InzlM0C45N0f1FtwKnpX2HE8CRH6I2XBkiBdS.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="3">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Date&rdquo;</strong>.<strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>Adjustment Type &ldquo;Normal or Abnormal&rdquo; </strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>Adjustment Type &ldquo;Increase or Decrease&rdquo; </strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select the Related item &ldquo;<strong>Add Qty</strong>&rdquo; based on selected stock adjustment type. </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Reason &ldquo;Optional&rdquo; </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click &ldquo;<strong>Save</strong>&rdquo;</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/vVvS3visnDDvYMoeW6iws2hyX2c1UqrP7Vwb0HII.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-15 07:24:33',
                'updated_at' => '2024-12-15 07:24:33',
                'deleted_at' => NULL,
            ),
            49 => 
            array (
                'id' => 50,
                'article_id' => 50,
                'title' => 'Store Transfer Steps',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 9.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span><strong><span style="font-size: 16.0pt; line-height: 107%; background: yellow;">Store Transfers Steps</span></strong></p>
<p style="margin: 6pt 0in 0in 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 8.0pt; line-height: 150%; background: yellow;">&nbsp;</span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 84px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 150%;">Go to Settings Module</span></strong></li>
<li style="margin: 6pt 0in 8pt 84px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 150%;">Store Settings</span></strong></li>
<li style="margin: 6pt 0in 8pt 84px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 150%;">&ldquo;Add&rdquo; New Stores </span></strong></li>
</ol>
<p style="margin: 6pt 0in 8pt 1in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 150%;">&nbsp;</span></strong></p>
<p style="margin: 6pt 0in 8pt 1in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/r3IzUjv58BuIm180kAjSzo2Y1eTO4Vd6HxVez0iD.png" /></span></strong></p>
<p style="margin: 6pt 0in 8pt 129pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">It is required to fill which is showing start mark boxes (<span style="color: red;">*</span>)</span></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 216px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the Location* </span></li>
<li style="margin: 6pt 0in 8pt 216px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Mention the Name of the &ldquo;Store&rdquo; *</span></li>
<li style="margin: 6pt 0in 8pt 216px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add Contact Number (Optional) </span></li>
<li style="margin: 6pt 0in 8pt 216px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Address (Optional)</span></li>
<li style="margin: 6pt 0in 8pt 216px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click the &ldquo;Active Button&rdquo; *</span></li>
<li style="margin: 6pt 0in 8pt 216px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Save the Store* </span></li>
</ol>
<p><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/pzxvaScWk1gTCewMcR89PVI11PTKowjIJ6ow3K3n.png" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 84px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 150%;">Store Transfer Module</span></strong></li>
<li style="margin: 6pt 0in 8pt 84px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 150%;">Add Stock Transfer&nbsp;</span></strong></li>
</ol>
<p style="line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 6pt 0in 8pt 84px;"><strong><span style="font-size: 12.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Rm02ZpYLIoVcOvgTyGmw7IYYQhgPXxegJzMie9xN.png" /></span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the <strong>&ldquo;Transaction Date&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the <strong>&ldquo;From Store&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select<strong> &ldquo;Location&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the <strong>&ldquo;To Store Name&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the <strong>&ldquo;Product&rdquo;</strong> which is needed to transfer from the main store to another store. </span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Add <strong>&ldquo;Transfer Qty&rdquo;</strong> Selected item How much needs to transfer from the main store to another store. </span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">When transferring these items if need any notes, can add them here <strong>&ldquo;Additional Note&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 120px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click &ldquo;<strong>Save</strong>&rdquo;</span></li>
</ol>
<p style="margin: 6pt 0in 8pt 1in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/xxKRcu9j6kaGtILFzvwjNZw7f6nKO5bYLR4hMixK.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-15 10:17:30',
                'updated_at' => '2024-12-15 10:17:30',
                'deleted_at' => NULL,
            ),
            50 => 
            array (
                'id' => 51,
                'article_id' => 51,
                'title' => 'Supplier Payment',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 8.0pt; line-height: 107%; color: #000066;">&nbsp;</span></strong></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Supplier Payment &ndash; in Supplier Page </span></strong></p>
<p style="margin: 0in 0in 0in 0.25in; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; color: red; background: yellow;">Please follow the documents and steps mentioned.</span></strong></p>
<p style="margin: 0in 0in 0in 0.75in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 1.0pt; line-height: 150%; background: yellow;">&nbsp;</span></strong></p>
<ol style="margin-top: 0in; margin-bottom: 0in;">
<li style="margin: 0in 0in 0in 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Go to Contacts, then Click &ldquo;Suppliers&rdquo;. </span></li>
<li style="margin: 0in 0in 0in 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">In &ldquo;<strong>All your Suppliers</strong>&rdquo; page, enter &ldquo;Supplier Name&rdquo; in the search box, then you will get only the selected Supplier.</span></li>
<li style="margin: 6pt 0in 6pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Click &ldquo;Action&rdquo; button, then click &ldquo;<strong>Pay Due Amount&rdquo;</strong>.</span></li>
</ol>
<p style="margin: 6pt 0in; text-indent: 45pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/BO84cftUMoUFVAOJxqMc9uHvIlsP4qCk78qpQzul.png" alt="A screenshot of a computerDescription automatically generated" width="599" height="592" /></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="4">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Enter <strong>&ldquo;Amount&rdquo; </strong>paid for the Supplier.<strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select <strong>&ldquo;Date&rdquo; </strong>which supplier is paid.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select the<strong> &ldquo;Payment Method&rdquo;. </strong>(How Supplier is paid)<strong> </strong>You get multiple payment types as below (Ignore other Payment Methods, if you get).</span></li>
</ol>
<p style="margin: 6pt 0in 8pt 1.25in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">&nbsp;Cash</span></p>
<p style="margin: 6pt 0in 8pt 1.25in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Banks</span></p>
<p style="margin: 6pt 0in 8pt 1.25in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="7">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">Select in the<strong> &ldquo;Accounting Module&rdquo; </strong>drop down as below</span>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">If selected</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> &ldquo;Cash&rdquo; </span></strong><span style="font-size: 12.0pt; line-height: 150%;">in the Payment Method, then</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> </span></strong><span style="font-size: 12.0pt; line-height: 150%;">Select</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> &ldquo;<span style="background: yellow;">Cash</span>&rdquo; </span></strong><span style="font-size: 12.0pt; line-height: 150%;">here</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">If selected</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> &ldquo;Cheques&rdquo; </span></strong><span style="font-size: 12.0pt; line-height: 150%;">in the Payment Method, then</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> </span></strong><span style="font-size: 12.0pt; line-height: 150%;">Select</span><strong><span style="font-size: 12.0pt; line-height: 150%;"> &ldquo;Banks&rdquo; </span></strong><span style="font-size: 12.0pt; line-height: 150%;">here. Then You will get more fields to enter cheque related details. Need to enter them.</span></li>
</ol>
</li>
</ol>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="8">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 150%;">After added payment details click<strong> &ldquo;Save&rdquo; </strong>button</span></li>
</ol>',
                'language' => 'en',
                'created_at' => '2024-12-15 10:27:22',
                'updated_at' => '2024-12-15 10:27:22',
                'deleted_at' => NULL,
            ),
            51 => 
            array (
                'id' => 52,
                'article_id' => 52,
            'title' => 'Guide for Postpaid bills (Purchase Order)',
                'content' => '<p style="margin: 0in; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 9.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="margin: 0in; text-align: center; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in; text-align: center; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><u><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">මිලදී ගැණුම් ඇණවුම්</span></u></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">පසු</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">ගෙවුම්</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">බිල්පත්</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">සඳහා</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">පහත</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">සඳහන්</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">පිළිවෙල</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">අනුගමනය</span><span style="font-size: 12.0pt; line-height: 107%;"> </span><span style="font-size: 12.0pt; line-height: 107%; font-family: \'Iskoola Pota\', sans-serif;">කරන්න</span><span style="font-size: 12.0pt; line-height: 107%;">.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><u><span style="font-size: 3.0pt; line-height: 107%;">&nbsp;</span></u></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><u><span style="font-size: 12.0pt; line-height: 107%;">Purchase Order</span></u></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;">Follow the below Guide Line for postpaid bills.</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>Purchases </strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">List Purchases&nbsp; Select the<strong> &ldquo;Purchase Order No&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/wR1ONCL9R7n9IQMGjeqCcHkiykivTCvcZU26usOk.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Action Button&rdquo;</strong></li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click<strong> &ldquo;Add Payment&rdquo;</strong><strong>&nbsp;</strong></li>
</ol>
<p style="line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif; margin: 0in 0in 8pt 0px;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/uRchclYWRc66EWFGrr6gQeKRjGzQZ2B2F67cgsw9.png" /></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&ldquo;Entrred Paid Amount&rdquo;</strong> for the<strong> </strong>Purchase Order</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the <strong>&ldquo;Paid Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the<strong> &ldquo;Payment Method&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select<strong> &ldquo;Accounting Module Account&rdquo; </strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add<strong> &ldquo;Payment Note&rdquo; </strong>if needed.</li>
<li style="margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>Save the entry. </strong></li>
</ol>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/JMYvqcOFgoWsmmIOxUvROrjM2l4SJ3N5V0R8FKP1.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-15 10:52:15',
                'updated_at' => '2024-12-15 10:52:15',
                'deleted_at' => NULL,
            ),
            52 => 
            array (
                'id' => 53,
                'article_id' => 53,
                'title' => 'VAT Settings',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%; font-family: Calibri, sans-serif;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">&nbsp;</span></strong><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">How to Configure VAT in SYZYGY Systems</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 5.0pt; color: #4c94d8;">&nbsp;</span></strong></p>
<p style="margin: 0in 0in 0in 0.5in; text-indent: -31.5pt; font-size: 11pt; font-family: Aptos, sans-serif;"><span style="color: red;">Note</span></p>
<p style="text-indent: -31.5pt; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">Most of the sections specially in the VAT Module will be applicable only for those who have purchased our VAT Module.</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">Purchases, Expenses and Sales details will be calculated, show in the reports from the date of VAT Module purchase. Will not calculate for the previous dates. Only in the VAT Module.</p>
<p style="margin: 0in 0in 0in 0.5in; text-indent: -31.5pt; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">For the Businesses who have already purchased VAT Module, will have to click Edit and Resave all the Settlements or Sales Invoices along with the Purchase and Expense invoices / forms.</p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8;">&nbsp;</span></strong></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">1. VAT Settings</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Settings Module (Image No 1)</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Business Settings (Image No 2)</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/7LOr53lGZGxAp6Tuu3D8Jg6W4x1E5TCUduXhsPTZ.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Tax page (Image No 3)</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Enter Tax Name Example VAT (Image No 4). Need to do only one time.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Enter Tax Number Example VAT Number of your Business. (Image No 5). Need to do only one time.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/F6L97ANiRrMk4msss2q0e8KlRH22Qp95q6T6LXmM.png" /></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="6">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Settings (Image No 6)</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="7">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Tax Rates (Image No 7)</li>
</ol>
<p style="font-size: 11pt; font-family: Aptos, sans-serif; margin: 0in 0in 0in 0px;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/LwBtm13YP3wC6ZdH7pQbKuxiUtknQirnsh6n4c9n.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="8">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Add Button (Image No 8)</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/0h6Mynpp9I3Qd5wdasgb2tFceepv6w36xhsQfx80.png" /></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="9">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Enter Tax Name Example VAT (Image No 9). Need to do only one time.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="10">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Enter Tax percentage Example 18 (Image No 10). Need to do only one time.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/RfHIXBU4Cw30GzyAeqjEdBhDf3zfC26DuulPyJhZ.png" /></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-15 11:04:10',
                'updated_at' => '2024-12-15 11:04:10',
                'deleted_at' => NULL,
            ),
            53 => 
            array (
                'id' => 54,
                'article_id' => 54,
                'title' => 'Add Purchase with VAT Invoice',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%; font-family: Calibri, sans-serif;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">&nbsp;</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">How to Configure VAT in SYZYGY Systems</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8;">&nbsp;</span></strong></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8;">Add Purchase (VAT)</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Purchase</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Add Purchase</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/oLWogwdHqboxe2vEtQNZX6Pvj2ojDuIVHjOmP2dX.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Select VAT Invoice ? to Yes.&nbsp;</li>
</ol>
<p style="font-size: 11pt; font-family: Aptos, sans-serif; margin: 0in 0in 0in 0px;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tvQTR5lS4J2SZYglKxcLVrbyAJ0qidCCkSCVPQHN.png" /></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-15 11:23:21',
                'updated_at' => '2024-12-15 11:23:21',
                'deleted_at' => NULL,
            ),
            54 => 
            array (
                'id' => 55,
                'article_id' => 55,
                'title' => 'VAT for Expenses',
                'content' => '<p>&nbsp;</p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><a name="_Hlk156911603"></a><strong><em><u><span style="font-size: 18.0pt; line-height: 107%; font-family: Calibri, sans-serif;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">&nbsp;</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">How to Configure VAT in SYZYGY Systems</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8;">&nbsp;</span></strong></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">3. VAT in Expenses</span></strong></p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Expenses</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Expense Categories</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Add Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">If need to mark for already added Expense category, then click Edit button in the Action column.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/CH751JUW0W1Zpg3NwQjd0pbVWDQeqVKAohFGW0IY.png" /></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click VAT Input Claimed button if VAT is applicable for the Expense Category. Need to do only one time.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="6">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Click Save button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/JxRvAD6loEdIgwzdUTAFy5iHAVsUS48FiUgSd0ke.png" /></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-15 11:39:48',
                'updated_at' => '2024-12-15 11:39:48',
                'deleted_at' => NULL,
            ),
            55 => 
            array (
                'id' => 56,
                'article_id' => 56,
                'title' => 'Add Expenses with VAT Invoice',
                'content' => '<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">&nbsp;</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%; font-family: Calibri, sans-serif;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 6pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; font-family: Calibri, sans-serif; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">&nbsp;</span></strong><strong><span style="font-size: 14.0pt; color: #4c94d8; background: yellow;">How to Configure VAT in SYZYGY Systems</span></strong></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><strong><span style="font-size: 14.0pt; background: yellow;">Add Expenses</span></strong></p>
<ol>
<li style="font-size: 11pt; font-family: Aptos, sans-serif;">Click Expenses&nbsp;</li>
<li style="font-size: 11pt; font-family: Aptos, sans-serif;">Click Add Expense&nbsp;</li>
<li style="font-size: 11pt; font-family: Aptos, sans-serif;">Select VAT Invoice to Yes if the Expense is having VAT.&nbsp;</li>
</ol>
<p style="text-indent: 49.5pt; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="text-indent: 49.5pt; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/PbAYdEJm0b2WbT1e0T41A2Gx8Cl6vnNwnx2RXer1.png" /></p>
<p style="text-indent: 49.5pt; margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Aptos, sans-serif;">Whenever the Purchases, Expenses and Sales of Products and Services done, system will Automatically calculate and show the related VAT details in the VAT Report.</li>
</ol>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;"><a name="_Hlk156911955"></a><span style="color: red;">Note</span></p>
<p style="margin: 0in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">Purchases, Expenses and Sales details will be calculated, show in the reports from the date of VAT Module purchase. Will not calculate for the previous dates.</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Aptos, sans-serif;">For the Businesses who have already purchased VAT Module, will have to click Edit and Resave all the Settlements or Sales Invoices along with the Purchase and Expense invoices / forms.</p>',
                'language' => 'en',
                'created_at' => '2024-12-15 12:59:09',
                'updated_at' => '2024-12-16 04:01:27',
                'deleted_at' => NULL,
            ),
            56 => 
            array (
                'id' => 57,
                'article_id' => 57,
                'title' => 'List Member and Add New Member',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Click Member Module</li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Click List Member</li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cwoRSQH0006um17QLdvelj4fEI8w4jpBtfUbBQs0.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date of Birth&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Province&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;District&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Electrorate&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Gramasevaka Area&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Gender&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Member Group&rdquo;</strong></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/0lmQ4Kuyp9OHljNMF4wlNHxOb7VLS4mor99N40RS.png" /></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; color: red; background: yellow;">How to add New Member</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="11">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> Button</li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/CSNyy7KTdloFQkxznCcXYFF8Waw50AwR3e8P2SmB.png" /></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Member Code&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Name&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Address&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Electrorate&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Gramasevaka Area&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number 1&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number 2&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number 3&rdquo;</strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;">Finally Click <strong>&ldquo;Save&rdquo;</strong> Button</li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 8pt 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/pKn3dbiwVr6xJyy4bhY8LS87yYBRLh0RttpA3xgs.png" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-24 10:19:13',
                'updated_at' => '2024-12-24 10:19:13',
                'deleted_at' => NULL,
            ),
            57 => 
            array (
                'id' => 58,
                'article_id' => 58,
                'title' => 'List Suggestions & Add New Suggestions',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Member Module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click List Suggestions</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/uTz2N5oZAf2edXiKyzm2QnJcMCljyGXk5OOQ65D7.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date Range&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Balamandala Area&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Main Areas&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Name of Suggestions&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Details of Suggestions&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select Is <strong>&ldquo;common problem&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Area which Involved&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;State of urgency&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Solution Given&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/GEr7wRWRdkpBVo3WlVswYlgCeosWorciazrBMFDk.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">How to add New Suggestions</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/YVDRbvtLNS5zx72Cn0N01MSXmKzhnNbU8soFjG9D.png" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="13">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Member&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Balamandalaya&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Service Area&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Heading&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Edit <strong>&ldquo;Heading&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/3zl1WoJ8spOqUujTPdMGgQzqc8eyRMeJGk6m9pEI.png" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="19">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Is common problem&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Area Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;State of Urgency&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Choose file Upload <strong>&ldquo;Document&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Remarks&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Wlt4vHQgI7Q2qy88uY6vyv1UuS4rG1FEJmNnUO1N.png" /></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-24 10:22:19',
                'updated_at' => '2024-12-24 10:22:19',
                'deleted_at' => NULL,
            ),
            58 => 
            array (
                'id' => 59,
                'article_id' => 59,
                'title' => 'Member Settings',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Member module</li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click Member Setting</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/2y0Ec1XKUmWR7kBcQtihtYFSBZ8ff3osb9MlSISm.png" width="254" height="208" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>How to add New Provinces</strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Provinces&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click<strong> &ldquo;Add&rdquo;</strong> Button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/RFDDVT90GtVr4Oa54C8hBXqUMhR1UpUsx2x7pnQv.png" width="624" height="215" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Province&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Country&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally Click <strong>&ldquo;Save&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/AFUIGDHOZQKx42ohgRbpbM18XEY1hNjFttVExs7r.png" width="624" height="342" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="8">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;District&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Y32i4gAe57HVdwNczqVDgXWJapvN9LmgN15K18mH.png" width="624" height="221" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="10">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;District&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Province&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button.</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/TVAQ9Z4TakIcKi1NAFUYwTXYTR8bmugneHGDOXfr.png" width="624" height="329" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="13">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Electrorate&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/b8xDAOfm9ZwOb9O4ncHG77sHTefDI6hLKErzUr9q.png" width="623" height="218" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="15">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Electrorate&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;District&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Province&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9zBTXDyzQTYWkrJhjsf6jwS6qa6TYqqprjzss1vU.png" width="624" height="396" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="19">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Gramasewa wasama&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/2oWYNedBpgtdEPOBkqXZ70mwoWkLZ83vfx9309tG.png" width="624" height="218" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="21">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Electrorate&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Auto load related <strong>&ldquo;Province&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Auto load related <strong>&ldquo;District&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Gramasewa wasama&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/zEugXs60P87HWxhZdPDJ485zBwoh961JfnWjtpP9.png" width="624" height="518" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="27">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Member Group&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo; </strong>button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/7GR8lxp4Exyx83bUwdQrKL8VkoSAC6kwHzFr4UrJ.png" width="624" height="226" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="29">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Member Group&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Q6QMd77gO6nFJxZ8OY5M6vinIvFQFTYbyLqBm9qF.png" width="624" height="330" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="32">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Service Area&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/vcrlBln3mGWtSSpvduUW3zn4wEOwKI6yHlDTT355.png" width="625" height="262" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="34">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Service Area&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/uCdXu1VIOJuSamyjHq9FYDepIQ7aRTkZhAIZfYAv.png" width="624" height="323" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="37">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Staff to Assign&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo; </strong>button</li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/U5lESopG5kp8Wm0IbXe05nAnap5GDaNVsVOHV6ZM.png" width="623" height="230" /></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="39">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Staff Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Add <strong>&ldquo;Designation&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save&rdquo;</strong> button</li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/wqUrbz7YcQIWwsnSYkDirJUnzeJ8r8Gu9BR3Bybr.png" width="624" height="406" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-24 10:34:33',
                'updated_at' => '2024-12-24 10:34:33',
                'deleted_at' => NULL,
            ),
            59 => 
            array (
                'id' => 60,
                'article_id' => 60,
                'title' => 'Member User Activities',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: red; background: yellow;">Following Steps</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Member Module/List Members/Action/Edit/Update</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: red;">When an added member&rsquo;s description is changed again, it will be displayed here.</span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: red;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Theg2cWWyhrSVYAnsGJrltpNt7HD5mQFdVbL3e19.png" width="625" height="241" /></span></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-24 10:35:49',
                'updated_at' => '2024-12-24 10:35:49',
                'deleted_at' => NULL,
            ),
            60 => 
            array (
                'id' => 61,
                'article_id' => 61,
                'title' => 'Add New Pump Operator',
                'content' => '<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 115%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 115%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 115%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Petro Module/Pumper Management</span></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/3i40i69joQnjw8H6AWpLI4A8uiFTLsh5zPUwvjGI.png" width="241" height="385" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="2">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select &ldquo;Pump Operator&rdquo;</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Add&rdquo;</span></strong><span style="font-size: 14.0pt; line-height: 150%;"> button</span></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/UBW4gINCwrTPsRLccaLcTcC8T2zBHSg9OPbTzKI7.png" width="624" height="267" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="4">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Name&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Address&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Mobile&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Land Line Number&rdquo;</strong><strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Date of birth&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;National ID Number&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Email Address&rdquo;</strong> <strong>&nbsp;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;User Name&rdquo;</strong><strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Passcode&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Re-enter passcode <strong>&ldquo;Confirm Passcode&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Opening Balance&rdquo;</span></strong><span style="font-size: 14.0pt; line-height: 150%;"> if needed</span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Location&rdquo;</span></strong></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/6ajOhuwXM07jBXDXtS0BptHuj6n34stulx31GEMr.png" width="624" height="543" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="16">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Commission Type&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Transaction Date&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Use for Admin\'s Operator Dashboard Login:*&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Can Minimize Full Screen:*&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click <strong>&ldquo;Save Button&rdquo;</strong></span></li>
</ol>
<p style="margin: 6pt 0in 10pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Et4MsU6yskU7PqtFKUzRuZ8s9pU9O6Uy6r6FCREq.png" width="624" height="283" /></p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:19:10',
                'updated_at' => '2024-12-26 06:19:10',
                'deleted_at' => NULL,
            ),
            61 => 
            array (
                'id' => 62,
                'article_id' => 62,
                'title' => 'Assign Pumps',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; background: yellow;">Pumper Dashboard Working Steps</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%;">Assign Pumps</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Click &ldquo;<strong>Black color button</strong>&rdquo;.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Select &ldquo;<strong>Petro Module</strong>&rdquo;.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Select &ldquo;<strong>Pumper Management</strong>&rdquo;.</span></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tKdFmhZxmgn0OLFkT3ief4CYWAYvcT84HDkWoeyl.png" width="266" height="547" /></span></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Click &ldquo;<strong>Daily Pump Status</strong>&rdquo;.</span></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ryIEbZM4aovUOAllVZY0jxPOs97lgYJzvfWAfUZl.png" width="625" height="273" /></span></p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Click <strong>&ldquo;+ Assign&rdquo;</strong> Button.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Select &ldquo;<strong>Pump Operator</strong>&rdquo;.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Select &ldquo;<strong>Pump</strong>&rdquo;.</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 107%;">Click &ldquo;<strong>Submit</strong>&rdquo; Button.</span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/hhn072tMzlrXJ5MvuCPe4xe800CVVD45AbRedVn3.png" width="624" height="278" /></span></p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:20:11',
                'updated_at' => '2024-12-26 06:20:11',
                'deleted_at' => NULL,
            ),
            62 => 
            array (
                'id' => 63,
                'article_id' => 63,
                'title' => 'Pumper Dashboard Login Steps & Receive Pumps',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><a name="_Hlk183367724"></a><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; background: yellow;">Pumper Dashboard Working Steps</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">In the system login page</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Login</strong>&rdquo; button</span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/U6fcclwCTWKIscEjwUTE1dZRlvgGSeFJmIbCDAkc.png" width="624" height="303" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select Your &rdquo; <strong>Business</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select your &ldquo;<strong>Login Display</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/eRGihX2cenxq06a2t08hwrzc6yBdkdfi9D7kZHiC.png" width="624" height="305" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%;">Now you can see the pumper dashboard login page</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Pump Operator</strong>&rdquo; password.</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click green color button &ldquo;<strong>Click to enter</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/Rqhz1xFOFXHLwADr0PNz0GjTLBOhwSuS4fwPqZGi.png" width="624" height="304" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%;">Pumper Dashboard/Login dashboard</span></strong></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/IsymiBCsaZlXmM4FBG8o3JBPl87lBwkOLUTq6XTP.png" width="625" height="301" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Receive Pump</strong>&rdquo;</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="6">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click selected pump</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/nciBvHd9iIqfXAn3cqjrf5NGJlVUOgiiMxy1GdPM.png" width="623" height="299" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="7">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Reconfirm meter</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Confirm meter Reading</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/hcdrsJijdWj07c7DbuDjna6pNjtynlN2q5Nlea7C.png" width="624" height="302" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:22:07',
                'updated_at' => '2024-12-26 06:22:07',
                'deleted_at' => NULL,
            ),
            63 => 
            array (
                'id' => 64,
                'article_id' => 64,
                'title' => 'Pumper Dashboard Add Payments',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><a name="_Hlk183377618"></a><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; background: yellow;">Pumper Dashboard Working Steps</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Cash</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Cash Amount</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Amount Correct? Click here</strong>&rdquo; button.</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Save&rdquo;</strong></span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/kVX8i0Q5WfjkyDAy8LHh6fChY3Ue4dsaebLgnFBO.png" width="624" height="302" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="5">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Card</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/FOa9T1t2GgAUrWQZHE6UQY0nmLa5qCIDmT0I86xZ.png" width="624" height="336" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="6">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Card Type</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Amount</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Add button</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Save button</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/liwZDQwWujlTQAPZzhivk6OPj9HIgSUXsCaBM0wb.png" width="624" height="318" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="10">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Cheque</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Customer</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Bank</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Cheque Number</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Cheque Date</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Cheque Amount</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Finalize</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/74XtNm9jh9H956KVKlmYcztXGU78niftCsZCD92t.png" width="624" height="235" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="17">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Credit</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/igPcSMYkCN4oPsnh8trKk2WJ0ybiUHWxCXG4kGRs.png" width="625" height="336" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="18">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Customer</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Order Number</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Order Date</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Customer Vehicle Number</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Product</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Qty</strong>&rdquo; (Quantity)</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Auto Load Amount (Before Discount)</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Customer vehicle number</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Add</strong>&rdquo; button</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">The relevant details are given below</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/BhJoFetZa7cbLAwQdJEppyfJU5np64PmERdw35NZ.png" width="624" height="339" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="28">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Payment Summery</strong>&rdquo; (All entered payments are displayed here)</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/j0B0WRleMOhruuWkFeM6zd7Px4ZXwwvzmlyUzSNs.png" width="624" height="300" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/XKw0Fc6WISsE8MnKY3QSqXNVONJ9B2fVO1uUNW4J.png" width="625" height="261" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:23:24',
                'updated_at' => '2024-12-26 06:23:24',
                'deleted_at' => NULL,
            ),
            64 => 
            array (
                'id' => 65,
                'article_id' => 65,
                'title' => 'Pumper Dashboard Add Other Sales',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><a name="_Hlk183377618"></a><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; background: yellow;">Pumper Dashboard Working Steps</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Other sales</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/RGrQhH9lWJ7OaB9BnDC4ymbahpIH7SW6paLj9HNV.png" width="624" height="300" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Product</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Units and price are auto loaded.</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Qty</strong>&rdquo;(Quantity)</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Green color button</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Amount correct? Click here</strong>&rdquo; button</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Shows all entered other sale details.</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Save or Save &amp; Print</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/syaVfhYHGmqJKlTbNQ15M9yaqzexvmaEKfyQeeMJ.png" width="623" height="302" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="9">
<li style="line-height: 150%; margin: 0in 0in 8pt 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>List other sales</strong>&rdquo; (Pumper dashboard).All other sales details are displayed here.</span></li>
</ol>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:24:36',
                'updated_at' => '2024-12-26 06:24:36',
                'deleted_at' => NULL,
            ),
            65 => 
            array (
                'id' => 66,
                'article_id' => 66,
                'title' => 'Pumper Dashboard Closing Meter',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; background: yellow;">Pumper Dashboard Working Steps</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Close Pump</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/69Y2XAA7XBzYAlTRRmS7sSKEoame4T825Ncc8xcd.png" width="624" height="300" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Closing meter</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/MD4SBXIpFvuWhq5u3v2TPEvYabX8d3L3HrBwPY3k.png" width="624" height="278" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Testing Liters</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Enter &ldquo;<strong>Closing Meter</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Green color button</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 8pt 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Save button</strong>&rdquo;</span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.25in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%;">Pump Number, Sale Price, Starting meter and Total amounts are auto loaded</span></strong></p>
<p style="margin: 0in 0in 8pt 0.25in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/nZEdoVx4UgRBfE8PuQ4foMnxpRH4barj6uclYAUE.png" width="624" height="302" /></span></strong></p>
<p style="line-height: 150%; margin: 0in 0in 8pt 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in 0in 8pt 0.25in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 0in 0in 0in 1.25in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:25:38',
                'updated_at' => '2024-12-26 06:25:38',
                'deleted_at' => NULL,
            ),
            66 => 
            array (
                'id' => 67,
                'article_id' => 67,
                'title' => 'Pumper Dashboard Close Shift',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; background: yellow;">Pumper Dashboard Working Steps</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Close shift</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/ypLbzz0MWQ8fObZu0xict0mEL5dpwxmRwxJj8EIg.png" width="624" height="300" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%;">Check all payments and other details.</span></strong></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Balance to Operator</strong>&rdquo; button.</span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/KRyNSLvQad0edU3Q25iFDImLYE0hnyUMqFBYGMAX.png" width="624" height="302" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Close shift</strong>&rdquo; button.</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">The relevant details are displayed below. (Close shift)</span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/fdAen9c9JawROlxOKPLQoCOUrP1YOHW2DxiuJ4Ya.png" width="623" height="295" /></p>
<p>&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:26:38',
                'updated_at' => '2024-12-26 06:26:38',
                'deleted_at' => NULL,
            ),
            67 => 
            array (
                'id' => 68,
                'article_id' => 68,
            'title' => 'Settlement (Pumper Dashbaord)',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; background: yellow;">Pumper Dashboard Working Steps</span></strong><strong><span style="font-size: 14.0pt; line-height: 107%;"> &ndash; <span style="color: red; background: yellow;">Settlement</span></span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Go to Petro module</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Settlement</strong>&rdquo;</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/fEkyQW134BVADJIgUzgIMxim1ADIeUJTsTarnLJ8.png" width="254" height="520" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="3">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Pump Operator</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Transaction Date</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Work &amp; shift no</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Select &ldquo;<strong>Pump No</strong>&rdquo;</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%;">Starting meter, Closing meter, Sold qty, Unit price and Testing qty are auto load.</span></strong></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Add</strong>&rdquo; button.</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">The relevant details are displayed below.</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/TKKyDH94dKvvSZIdnovpqw4xJQXtBJuJcOhr4f6t.png" width="624" height="270" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="10">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Other sales</strong>&rdquo; button</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">After selecting the &nbsp;pump operator and shift number , related other sale details will auto loaded.</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/qjfPfoCzHIhB5ouskAr7G2izcDmT5aAJ637nFBUF.png" width="624" height="274" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="12">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Payment</strong>&rdquo; button</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Click &ldquo;<strong>Payment finalized</strong>&rdquo; button</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/TFBALqrTuu7vsCTnfqetZ4Zcn8AhVxfmfrwBy0Qr.png" width="624" height="268" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="14">
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Check all related payment details.</span></li>
<li style="line-height: 150%; margin: 0in 0in 0in 0px; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;">Finally click &ldquo;<strong>Save</strong>&rdquo;.</span></li>
</ol>
<p style="line-height: 150%; margin: 0in 0in 0in 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 16.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/dvbvtKZoOIUAZ3rrAdGpI1E50libr8IZlkYEkky2.png" width="624" height="274" /></span></p>
<p style="line-height: 150%; margin: 0in 0in 8pt 0.5in; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%;">&nbsp;</span></strong></p>
<p style="line-height: 150%; margin: 0in 0in 8pt; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p>&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:28:09',
                'updated_at' => '2024-12-26 06:28:09',
                'deleted_at' => NULL,
            ),
            68 => 
            array (
                'id' => 69,
                'article_id' => 69,
                'title' => 'Pumper Dashboard Enter Meters with payments',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: red; background: yellow;">Enter Meters with Payments</span></strong></p>
<p style="margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: red;">How to add &ldquo;Compulsory to enter meter reading in payment page&rdquo;</span></strong></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Go to Petro Module</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Pumper dashboard settings</span></li>
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 12.0pt; line-height: 107%; color: black;">&ldquo;Compulsory to enter meter reading in payment page&rdquo;</span></strong></li>
</ol>
<p style="margin: 0in 0in 0in 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;YES&rdquo;</strong></span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Finally click <strong>&ldquo;Save&rdquo;</strong></span></li>
</ol>
<p style="margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/oMjcmGv6631E8q7rZ07E3oBpAu7xbzlu7V4UboqC.png" width="625" height="221" /></span></p>
<p style="text-align: justify; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Go to pumper dashboard, Click payment</span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="text-align: justify; margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Enter Meters&rdquo;</strong></span></li>
</ol>
<p style="text-align: justify; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/9pVrs9MfeUymBasSWKirxS9HOEpraWl2lyTWckai.png" width="623" height="220" /></span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="text-align: justify; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Enter <strong>&ldquo;New meter value&rdquo;</strong></span></li>
<li style="text-align: justify; margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Finalize&rdquo;</strong> button.</span></li>
</ol>
<p style="text-align: justify; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/tOig3EvN650JBzMHrYnE4PC2zT6jLXYdPTvHjxfo.png" width="625" height="295" /></span></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="4">
<li style="text-align: justify; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Payment method&rdquo;.</strong> Select the payment you want. If you select card, cheque and credit, enter other details related to it.</span></li>
<li style="text-align: justify; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Enter <strong>&ldquo;Only cash amount&rdquo;</strong>. Fill the relevant form for other payment methods.</span></li>
<li style="text-align: justify; margin: 0in 0in 0in 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Amount correct? Click here&rdquo;</strong>. Only cash payment.</span></li>
<li style="text-align: justify; margin: 0in 0in 8pt 0px; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">Click <strong>&ldquo;Save&rdquo;</strong></span></li>
</ol>
<p style="text-align: justify; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/vUuR8tkR04MOyMwPShYyOF9efGpxG0HAahh40cMX.png" width="624" height="341" /></span></p>
<p style="text-align: justify; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;">If you want to make another payment from the first entered meter, click <strong>&ldquo;YES&rdquo;</strong>. If there is no other payment, click <strong>&ldquo;NO&rdquo;</strong> (Image 8,9) For other payment methods, enter the meter again and make the corresponding payment.</span></p>
<p style="text-align: justify; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 12.0pt; line-height: 107%; color: black;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/YUp7RPmYaMZ4I8RHZz8okVCnFxKZumoWRtcy8TLT.png" width="624" height="253" /></span></p>
<p style="text-align: justify; margin: 0in 0in 8pt 0.5in; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:31:56',
                'updated_at' => '2024-12-26 06:31:56',
                'deleted_at' => NULL,
            ),
            69 => 
            array (
                'id' => 70,
                'article_id' => 70,
                'title' => 'Add Expenses',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 6pt 0in 8pt 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Add Expenses &nbsp;</span></strong></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;">Click Expenses Module / Add Expenses</span></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/cSqLFz69FL8O2nDYp79cWpo3NE1uekWJfMPgyPmN.png" width="256" height="326" /></span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">&nbsp;Add <strong>&ldquo;Select the Category&rdquo;</strong> </span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Date&rdquo;</strong>.<strong> </strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Expenses For&rdquo;</span></strong></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Add </span><strong><span style="font-size: 14.0pt; line-height: 150%;">&ldquo;Fleet&rdquo;</span></strong></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Expenses for Contact&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Enter <strong>&ldquo;Total Amount&rdquo;</strong></span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/a4JOtkENsU3Ke2VggKFDm1ARVJIF4DOlXazGei5M.png" width="786" height="351" /></span></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: red;">How to add Expenses Payment</span></strong></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Cash Payment</span></strong><span style="font-size: 14.0pt; line-height: 150%; color: black;"> </span></p>
<ol style="list-style-type: lower-alpha; margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Amount will show auto.</span></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select the Related Payment Type &ldquo;<strong>Petty Cash / Cash</strong>&rdquo; </span></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Accounting Module &ndash; Petty cash&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Add Note If needed.</span></li>
<li style="margin: 6pt 0in 8pt 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click &ldquo;<strong>Save or Save &amp; Print</strong>&rdquo;</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/829OAMK5lUSzJTjZOln9mKheRTxHs2L6Kn3DyQGI.png" width="786" height="334" /></span></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Bank Payment</span></strong></p>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Amount will show auto.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Select <strong>&ldquo;Payment Method - Bank&rdquo;</strong>.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Select <strong>&ldquo;Accounting Module &ndash; Bank Account&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Enter <strong>&ldquo;Cheque Number&rdquo;</strong>.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Select <strong>&ldquo;Cheque Date&rdquo;.</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Enter <strong>&ldquo;Payment Note&rdquo;</strong> If needed.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;">Click <strong>&ldquo;Save &amp; Print or Save&rdquo;</strong>.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%; color: black;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/fSXeFSvyRTY7U6CLdKFxJA1XCGihHIoFjIGl1Ih3.png" width="786" height="276" /></span></p>
<p style="margin: 6pt 0in 8pt; text-indent: 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 150%; color: #c00000; background: yellow;">Credit Expenses Payment</span></strong></p>
<ol style="list-style-type: lower-alpha; margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Amount will show auto.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Payment Method &ndash; Credit Expenses&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Select <strong>&ldquo;Accounting Module &ndash; Account Payable&rdquo;</strong></span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Add Payment Note if needed.</span></li>
<li style="margin: 6pt 0in 8pt 24px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;">Click <strong>&ldquo;Save &amp; Print or Save&rdquo;</strong>.</span></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="font-size: 14.0pt; line-height: 150%;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/LLIMnvscniNJujnTyDS4VjGQeiYk0lUZsWWFs6NC.png" width="786" height="242" /></span></p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:39:49',
                'updated_at' => '2024-12-26 06:39:49',
                'deleted_at' => NULL,
            ),
            70 => 
            array (
                'id' => 71,
                'article_id' => 71,
                'title' => 'Add New Customer',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><span style="background: yellow;">Add New Customer</span></p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;Go to Contact Module</p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;Customer</p>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/TwURAkY2PpHoPTy6FBxZ8KQpdTzUQZrrgxkOd2iv.png" width="254" height="458" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;">
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/uS9JOnOAGWNEcmbzB7m9kKzEvB03kllTz49GpOBM.png" width="624" height="228" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="2">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Contact Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Customer Name&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Need to send SMS Type&rdquo;&nbsp; &ldquo;Yes or No&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Credit Notification Type&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Tax Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;VAT Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Opening Balance&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Pay term&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Customer Group&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Credit Limit&rdquo;</strong></li>
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Transaction Date&rdquo;</strong></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/mcw1FpW4ebnuqkXJsTuNG0StF1h4TPNWrAkuP8Gn.png" width="624" height="346" /></p>
<ol style="margin-bottom: 0in; margin-top: 0px;" start="13">
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Email&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Mobile Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Altranate contact number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Land line number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Vehicle Number&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;Address, Address line 2, Address line 3&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;City, State, Country and Land mark&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Enter <strong>&ldquo;NIC or Passport No&rdquo;</strong></li>
<li style="margin: 0in 0in 10pt 0px; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">Finally click <strong>&ldquo;Save&rdquo; button.</strong></li>
</ol>
<p style="margin: 0in 0in 10pt; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/TBOBInzsVlsuGHa3vKaSZVgIHQhUjJ3oBm6ljGUt.png" width="625" height="408" /></p>
<p style="margin: 0in 0in 10pt 0.25in; line-height: 115%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:41:45',
                'updated_at' => '2024-12-26 06:41:45',
                'deleted_at' => NULL,
            ),
            71 => 
            array (
                'id' => 72,
                'article_id' => 72,
                'title' => 'Add New Product',
                'content' => '<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 1.0pt; line-height: 107%;">&nbsp;</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="text-align: center; margin: 0in 0in 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 6pt; margin-bottom: 0in;">
<li style="margin: 6pt 0in 0in 0px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">Add New Product</span></strong></li>
</ol>
<p style="margin: 6pt 0in 8pt 0.5in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;">Product Module/Add product</span></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%;"> <img style="width: 193.2pt; height: 453.6pt; visibility: visible;" src="https://coolishadi.vimi50.site/public/uploads/articles/images/wmBypT5cvuBtNOSIizl58C3zbCnvQgzkwYW0NPKu.png" /></span></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add<strong> &ldquo;Product Name&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Product code will come auto if need can add own <strong>&ldquo;Code&rdquo; (SKU)</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the applicable units</li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the Category<strong> &ldquo;Other Items&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the Sub Category<strong> &ldquo;Lubricants&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">If need add<strong> &ldquo;Alert Qty&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select the Stock Account<strong> &ldquo;Finished Goods Account&rdquo; or appropriate account from the drop down list</strong></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img style="width: 546pt; height: 235.2pt; visibility: visible;" src="https://coolishadi.vimi50.site/public/uploads/articles/images/t6F9ueCpzgyo7j361ERVZAXwDolF7tQMxx8myF2C.png" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="8">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Item<strong> &ldquo;Purchase Price&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add Item<strong> &ldquo;Selling Price&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Add Save &amp; Opening Stock&rdquo; </strong></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img style="width: 546pt; height: 113.4pt; visibility: visible;" src="https://coolishadi.vimi50.site/public/uploads/articles/images/2XS1O4mqg2UO95Vs4a3AbqqGUH9YcknJNdYvt7G8.png" /></strong></p>
<ol style="margin-top: 6.0pt; margin-bottom: 8.0pt;" start="11">
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add the <strong>&ldquo;Opening Qty&rdquo;</strong> here<strong> (if there any opening stock)</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Select <strong>&ldquo;Date&rdquo;</strong></li>
<li style="margin: 6pt 0in 8pt 72px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Save Button&rdquo;</strong></li>
</ol>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><img style="width: 545.4pt; height: 187.2pt; visibility: visible;" src="https://coolishadi.vimi50.site/public/uploads/articles/images/OFs0QomyNXacbOgWciKukA7IwZRaIn5PvmmFXOCg.png" /></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 150%; background: yellow;">If purchased any new item to the list Add without opening qty. after added 1 to 9 details, Click Save Button</span></strong><strong><span style="font-size: 14.0pt; line-height: 150%;">. </span></strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>
<p style="margin: 6pt 0in 8pt; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong>&nbsp;</strong></p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:43:47',
                'updated_at' => '2024-12-26 06:43:47',
                'deleted_at' => NULL,
            ),
            72 => 
            array (
                'id' => 73,
                'article_id' => 73,
                'title' => 'Product Price Change',
                'content' => '<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><em><u><span style="font-size: 18.0pt; line-height: 107%;">SYZYGY EazyPetRo Software Help Guide</span></u></em></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 16.0pt; line-height: 107%; color: #006600;">Accessible Anywhere Anytime, ALWAYS LIVE</span></strong></p>
<p style="margin: 0in 0in 8pt; text-align: center; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; line-height: 107%; color: #000066;">Manage Your Business, NOT THE SYSTEM, Log on and Go, IT\'S REAL SIMPLE</span></strong></p>
<p style="margin: 0in; text-align: center; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 14.0pt; color: red; background: yellow;">Price Change Steps</span></strong></p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<ol style="margin-top: 0in; margin-bottom: 0in;">
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Product Module</li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">List Product</li>
</ol>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/q0h0UfS0aSHIvuDtuZzvPB8YfaYnJj06e1XlNEgS.png" width="254" height="422" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="3">
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click <strong>&ldquo;Action&rdquo;</strong> (Search by category or product name and click action button)</li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Go to <strong>&ldquo;Edit&rdquo;</strong></li>
</ol>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/W9NJN5lk8wSwYhGoyUFYtIMInPoMYNPzJosCt5t1.png" width="623" height="222" /></p>
<ol style="margin-top: 0in; margin-bottom: 0in;" start="5">
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add the <strong>&ldquo;Purchase Price&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Add the <strong>&ldquo;Selling Price&rdquo;</strong></li>
<li style="margin: 0in 0in 0in 48px; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">Click<strong> &ldquo;Update&rdquo;</strong> it.</li>
</ol>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>
<p style="margin: 0in; line-height: 150%; font-size: 11pt; font-family: Calibri, sans-serif;"><img src="https://coolishadi.vimi50.site/public/uploads/articles/images/73RiTuV15O8QQe7r9D5OfUWXxNb0QzaWz8fjdRhp.png" width="623" height="235" /></p>
<p style="margin: 0in; line-height: normal; font-size: 11pt; font-family: Calibri, sans-serif;">&nbsp;</p>',
                'language' => 'en',
                'created_at' => '2024-12-26 06:47:11',
                'updated_at' => '2024-12-26 06:47:11',
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}