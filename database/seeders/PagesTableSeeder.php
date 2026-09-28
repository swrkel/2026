<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('pages')->delete();
        
        \DB::table('pages')->insert(array (
            0 => 
            array (
                'id' => 1,
                'page_name' => 'home',
                'section_name' => 'banner',
                'section_title' => 'banner_title',
                'section_content' => 'Manage Your Filling Station in a Smart way. Save Time & Cost',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            1 => 
            array (
                'id' => 2,
                'page_name' => 'home',
                'section_name' => 'banner',
                'section_title' => 'banner_description',
                'section_content' => 'SYZYGY Ezy PetRo International is the Ideal Software for any Filling Station to Manage their Sales, Stocks, Credit Sales, Card Sales, etc in the Right way.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            2 => 
            array (
                'id' => 3,
                'page_name' => 'home',
                'section_name' => 'banner',
                'section_title' => 'banner_button_1',
                'section_content' => 'Login now',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            3 => 
            array (
                'id' => 4,
                'page_name' => 'home',
                'section_name' => 'banner',
                'section_title' => 'banner_button_1_link',
                'section_content' => '/login',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            4 => 
            array (
                'id' => 5,
                'page_name' => 'home',
                'section_name' => 'banner',
                'section_title' => 'banner_button_2',
                'section_content' => 'How it works',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            5 => 
            array (
                'id' => 6,
                'page_name' => 'home',
                'section_name' => 'banner',
                'section_title' => 'banner_button_2_link',
                'section_content' => '#how-it-works',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            6 => 
            array (
                'id' => 7,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_mini_title',
                'section_content' => 'How it works?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            7 => 
            array (
                'id' => 8,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_title',
                'section_content' => 'Give the Right Profit and Savings.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            8 => 
            array (
                'id' => 9,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_description',
                'section_content' => 'Easy, Fast and Accurate. Helps you to know all about your Filling Station Operations at anytime from anywhere.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            9 => 
            array (
                'id' => 10,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_li_title_1',
                'section_content' => 'Easy to work',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            10 => 
            array (
                'id' => 11,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_li_title_2',
                'section_content' => 'Could complete work within a Quicker time',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            11 => 
            array (
                'id' => 12,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_li_title_3',
                'section_content' => 'Auto Calculate Meter Sales',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            12 => 
            array (
                'id' => 13,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_title_1',
                'section_content' => 'Autoload Last Meter',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            13 => 
            array (
                'id' => 14,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_description_1',
                'section_content' => 'System Auto loads last meters, calculate and show the correct amount to settle for each Nozzle. No Errors, No Shorts.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            14 => 
            array (
                'id' => 15,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_title_2',
                'section_content' => 'With Complete Accounting Module',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            15 => 
            array (
                'id' => 16,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_description_2',
                'section_content' => 'Now you can view the status of your Business at any time with complete Accounting. Nothing will miss.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            16 => 
            array (
                'id' => 17,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_title_3',
                'section_content' => 'Know Profit & Loss of your Business',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            17 => 
            array (
                'id' => 18,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_description_3',
                'section_content' => 'Now you can view Profit & Loss for any duration, any product, Product Category, for each Invoice, Brand, Branch, Customer etc',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            18 => 
            array (
                'id' => 19,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_title_4',
                'section_content' => 'Leader in the Field',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            19 => 
            array (
                'id' => 20,
                'page_name' => 'home',
                'section_name' => 'works',
                'section_title' => 'work_card_description_4',
                'section_content' => 'Many Filling stations successfully use Ezy PetRo International. Planning to Introduce it to other countries too soon.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            20 => 
            array (
                'id' => 21,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_mini_title',
                'section_content' => 'Why SYZYGY EzyPetRo International?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            21 => 
            array (
                'id' => 22,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_title',
                'section_content' => 'Special features of SYZYGY EzyPetRo International',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            22 => 
            array (
                'id' => 23,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_title_1',
                'section_content' => 'Calculate Sales, Stocks & Credit Sales to the last cent',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            23 => 
            array (
                'id' => 24,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_1',
                'section_content' => 'No more Losses now. Shows where your Money is. You can control every aspect of the Business with the Right decisions, with the help of SYZYGY EzyPetRo International',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            24 => 
            array (
                'id' => 25,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_2',
                'section_content' => 'Pump Operator Management',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            25 => 
            array (
                'id' => 26,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_2',
                'section_content' => 'Gives all the details related to pump operators such as Shorts, Excesses, Sales amounts, Monthly Sales Volume, Shortage recovered etc.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            26 => 
            array (
                'id' => 27,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_3',
                'section_content' => 'Customer Management',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            27 => 
            array (
                'id' => 28,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_3',
                'section_content' => 'Gives all the details related to Customers such as Sales, Payments, Security Deposits, Customer Ledgers, Customer Limits etc.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            28 => 
            array (
                'id' => 29,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_4',
                'section_content' => 'Customer Statements Direct from the System',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            29 => 
            array (
                'id' => 30,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_4',
                'section_content' => 'System auto creates Customer Statements for the Customers for the selected period. Auto Lock previous date period statements. Shows the current Balance Amount in detail. Easy for the customer to Understand and clear.  No errors or duplicates. entries.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            30 => 
            array (
                'id' => 31,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_5',
                'section_content' => 'Know Cash, Bank, Card, Total Customer Balances in a second',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            31 => 
            array (
                'id' => 32,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_5',
                'section_content' => 'All the details related to Day’s operation in one single page. Gives accurate balances of Cash, cards, Cheques in Hand, Customers. And day’s deposits too. No Mistakes now.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            32 => 
            array (
                'id' => 33,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_6',
                'section_content' => 'Know Total Stock Values',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            33 => 
            array (
                'id' => 34,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_6',
                'section_content' => 'Gives a clear picture of the Stock Values with the press of a button. No need to over spend on unnecessary stocks.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            34 => 
            array (
                'id' => 35,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_7',
                'section_content' => 'Know who does the most sales for the Business',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            35 => 
            array (
                'id' => 36,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_7',
                'section_content' => 'Gives the details of the staff Sales for a Week, Month, Year or any desired period. Easy to Recognize the best Sellers.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            36 => 
            array (
                'id' => 37,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_8',
                'section_content' => 'Control Your Purchases',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            37 => 
            array (
                'id' => 38,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_8',
                'section_content' => 'Know the total purchase and payment due for the suppliers. Can manage cash flow easily with this information.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            38 => 
            array (
                'id' => 39,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_9',
                'section_content' => 'Control Your Expenses',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            39 => 
            array (
                'id' => 40,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_9',
                'section_content' => 'Know the total Expenses of the Business. Can analyze and control unnecessary expenses.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            40 => 
            array (
                'id' => 41,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_10',
                'section_content' => 'Clean UI Design',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            41 => 
            array (
                'id' => 42,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_10',
                'section_content' => 'We creafted all designs professionally. It made with latest frameworks.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            42 => 
            array (
                'id' => 43,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_11',
                'section_content' => 'Faster Loading',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            43 => 
            array (
                'id' => 44,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_11',
                'section_content' => 'We give more importance for page load. Your digital card load faster than normal webpages.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            44 => 
            array (
                'id' => 45,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_12',
                'section_content' => 'Unique Link',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            45 => 
            array (
                'id' => 46,
                'page_name' => 'home',
                'section_name' => 'features',
                'section_title' => 'feature_card_description_12',
                'section_content' => 'Your name or business whatever it is. You can generate your business card link as per your choice.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            46 => 
            array (
                'id' => 47,
                'page_name' => 'home',
                'section_name' => 'pricing',
                'section_title' => 'pricing_mini_title',
                'section_content' => 'Pricing',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            47 => 
            array (
                'id' => 48,
                'page_name' => 'home',
                'section_name' => 'pricing',
                'section_title' => 'pricing_title',
                'section_content' => 'Choose your best plan',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            48 => 
            array (
                'id' => 49,
                'page_name' => 'home',
                'section_name' => 'pricing',
                'section_title' => 'pricing_subtitle',
                'section_content' => 'Good way to show Your involvement with Professional Smart Digital Technologies.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            49 => 
            array (
                'id' => 50,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_title',
                'section_content' => 'Frequently Asked Question',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            50 => 
            array (
                'id' => 51,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_description',
                'section_content' => 'The most common questions which will help you.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            51 => 
            array (
                'id' => 52,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_question_1',
                'section_content' => 'How Long my cards will be in this system?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            52 => 
            array (
                'id' => 53,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_answer_1',
                'section_content' => 'As long as your renewal is active. If the annual renewal expires, then within 30 days’ time, all the data in the system will get auto deleted. 
',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            53 => 
            array (
                'id' => 54,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_question_2',
                'section_content' => 'Can I make the payments in Installments for the system?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            54 => 
            array (
                'id' => 55,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_answer_2',
                'section_content' => 'We always help our Clients. Please contact us with your expectations.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            55 => 
            array (
                'id' => 56,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_question_3',
                'section_content' => 'Can I add other Modules later on?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            56 => 
            array (
                'id' => 57,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_answer_3',
                'section_content' => 'Yes, You can. Please contact us for more details. ',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            57 => 
            array (
                'id' => 58,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_question_4',
                'section_content' => 'Can I get refund after purchased the system?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            58 => 
            array (
                'id' => 59,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_answer_4',
                'section_content' => 'This is an online System, so, refunds will not be done. ',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            59 => 
            array (
                'id' => 60,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_question_5',
                'section_content' => 'How many Nozzles included in the system by default?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            60 => 
            array (
                'id' => 61,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_answer_5',
                'section_content' => '12 Nozzles. But could upgrade to any number of Nozzles with additional payments ',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            61 => 
            array (
                'id' => 62,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_question_6',
                'section_content' => 'Do you provide Support throughout?',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            62 => 
            array (
                'id' => 63,
                'page_name' => 'faq',
                'section_name' => 'faq',
                'section_title' => 'faq_answer_6',
                'section_content' => 'Yes, as long as the System validity is there. We request to send any clarifications by Email or WhatsApp, so it will avoid any misunderstandings and clear for all. You can contact us by Email to syzygysec@gmail.com
',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            63 => 
            array (
                'id' => 64,
                'page_name' => 'footer support email',
                'section_name' => 'support',
                'section_title' => 'support_email',
                'section_content' => 'syzygysec@gmail.com',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            64 => 
            array (
                'id' => 65,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_title',
                'section_content' => 'Privacy Policy for SYZYGY Software',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            65 => 
            array (
                'id' => 66,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'One of our main priorities is the privacy of our visitors to the website. This Privacy Policy contains types of information that is collected and recorded by SYZYGY and how we use it.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            66 => 
            array (
                'id' => 67,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'If you have any questions or require more information about our Privacy Policy, please contact us. We assure that your details will be secured with us.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            67 => 
            array (
                'id' => 68,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'This Privacy Policy applies only to and is valid for visitors to our website with regards to the information that they share and/or collect in SYZYGY. This policy is not applicable to any information collected by any other means except this website.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            68 => 
            array (
                'id' => 69,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'Consent',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            69 => 
            array (
                'id' => 70,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'By using our website, you hereby consent to our Privacy Policy and agree to its terms.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            70 => 
            array (
                'id' => 71,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'Information we collect',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            71 => 
            array (
                'id' => 72,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The personal information entered here only used for the purpose of related to this website.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            72 => 
            array (
                'id' => 73,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'If you contact us directly, we may receive additional information necessary.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            73 => 
            array (
                'id' => 74,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'When you register for an Account, we may ask for your contact information, including items such as name, company name, address, email address, and telephone number.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            74 => 
            array (
                'id' => 75,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'How we use your information',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            75 => 
            array (
                'id' => 76,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'We use the information we collect in various ways, including to:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            76 => 
            array (
                'id' => 77,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => '1. Provide, operate, and maintain our website
2. Improve, personalize, and expand our website
3. Understand and analyze how you use our website
4. Develop new products, services, features, and functionality
5. Communicate with you, either directly or through one of our partners, including for customer service, to provide you with updates and other information relating to the website, and for marketing and promotional purposes
6. Send you emails
7. Find and prevent fraud',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            77 => 
            array (
                'id' => 78,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'Log Files',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            78 => 
            array (
                'id' => 79,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'SYZYGY follows a standard procedure of using log files. These files log visitors when they visit websites. All hosting companies do this and a part of hosting services analytics.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            79 => 
            array (
                'id' => 80,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The information collected by this site is for analyzing trends, administering the site, tracking users movement on the website, and gathering demographic information.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            80 => 
            array (
                'id' => 81,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'Cookies and Web Beacons',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            81 => 
            array (
                'id' => 82,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Like any other website, SYZYGY uses cookies. These cookies are used to store information including visitors preferences, and the pages on the website that the visitor accessed or visited. The information is used to optimize the users experience by customizing our web page content based on visitors browser type and/or other information.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            82 => 
            array (
                'id' => 83,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'For more general information on cookies, please read "What Are Cookies".',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            83 => 
            array (
                'id' => 84,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'Advertising Partners Privacy Policies',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            84 => 
            array (
                'id' => 85,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'You may consult this list to find the Privacy Policy for each of the advertising partners of SYZYGY.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            85 => 
            array (
                'id' => 86,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Third-party ad servers or ad networks uses technologies like cookies, JavaScript, or Web Beacons that are used in their respective advertisements and links that appear on SYZYGY, which are sent directly to users browser. They automatically receive your IP address when this occurs. These technologies are used to measure the effectiveness of their advertising campaigns and/or to personalize the advertising content that you see on websites that you visit.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            86 => 
            array (
                'id' => 87,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Note that SYZYGY has no access to or control over these cookies that are used by third-party advertisers.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            87 => 
            array (
                'id' => 88,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'Third Party Privacy Policies',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            88 => 
            array (
                'id' => 89,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'SYZYGY Privacy Policy does not apply to other advertisers or websites. Thus, we are advising you to consult the respective Privacy Policies of these third-party ad service providers for more detailed information. It may include their practices and instructions about how to opt-out of certain options.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            89 => 
            array (
                'id' => 90,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'You can choose to disable cookies through your individual browser options. To know more detailed information about cookie management with specific web browsers, it can be found at the browsers respective websites.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            90 => 
            array (
                'id' => 91,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
            'section_content' => 'CCPA Privacy Rights (Do Not Sell My Personal Information)',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            91 => 
            array (
                'id' => 92,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Under the CCPA, among other rights, California consumers have the right to:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            92 => 
            array (
                'id' => 93,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Request that a business that collects a consumers personal data disclose the categories and specific pieces of personal data that a business has collected about consumers.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            93 => 
            array (
                'id' => 94,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Request that a business delete any personal data about the consumer that a business has collected.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            94 => 
            array (
                'id' => 95,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Request that a business that sells a consumers personal data, not sell the consumers personal data.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            95 => 
            array (
                'id' => 96,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            96 => 
            array (
                'id' => 97,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'GDPR Data Protection Rights',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            97 => 
            array (
                'id' => 98,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'We would like to make sure you are fully aware of all of your data protection rights. Every user is entitled to the following:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            98 => 
            array (
                'id' => 99,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The right to access – You have the right to request copies of your personal data. We may charge you a fee for this service.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            99 => 
            array (
                'id' => 100,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The right to rectification – You have the right to request that we correct any information you believe is inaccurate. You also have the right to request that we complete the information you believe is incomplete.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            100 => 
            array (
                'id' => 101,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The right to erasure – You have the right to request that we erase your personal data, under certain conditions.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            101 => 
            array (
                'id' => 102,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The right to restrict processing – You have the right to request that we restrict the processing of your personal data, under certain conditions.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            102 => 
            array (
                'id' => 103,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The right to object to processing – You have the right to object to our processing of your personal data, under certain conditions.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            103 => 
            array (
                'id' => 104,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'The right to data portability – You have the right to request that we transfer the data that we have collected to another organization, or directly to you, under certain conditions.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            104 => 
            array (
                'id' => 105,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            105 => 
            array (
                'id' => 106,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_title',
                'section_content' => 'Children’s Information',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            106 => 
            array (
                'id' => 107,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'Another part of our priority is adding protection for children while using the internet. We encourage parents and guardians to observe, participate in, and/or monitor and guide their online activity.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            107 => 
            array (
                'id' => 108,
                'page_name' => 'privacy',
                'section_name' => 'privacy',
                'section_title' => 'privacy_content_description',
                'section_content' => 'SYZYGY does not knowingly collect any Personal Identifiable Information from children under the age of 13. If you think that your child provided this kind of information on our website, we strongly encourage you to contact us immediately and we will do our best efforts to promptly remove such information from our records.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            108 => 
            array (
                'id' => 109,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'Terms and Conditions',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            109 => 
            array (
                'id' => 110,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'Welcome to SYZYGY EzyPetRo International!',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            110 => 
            array (
                'id' => 111,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'These terms and conditions outline the rules and regulations for the use of SYZYGY Website.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            111 => 
            array (
                'id' => 112,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'By accessing this website we assume you accept these terms and conditions. Do not continue to use this site, if you do not agree to take all of the terms and conditions stated on this page.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            112 => 
            array (
                'id' => 113,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'The following terminology applies to these Terms and Conditions, Privacy Statement and Disclaimer Notice and all Agreements: "Client", "You" and "Your" refers to you, the person log on this website and compliant to the Company’s terms and conditions. "The Company", "Ourselves", "We", "Our" and "Us", refers to our Company. "Party", "Parties", or "Us", refers to both the Client and ourselves. All terms refer to the offer, acceptance and consideration of payment necessary to undertake the process of our assistance to the Client in the most appropriate manner for the express purpose of meeting the Client’s needs in respect of provision of the Company’s stated services, in accordance with and subject to, prevailing law of Netherlands. Any use of the above terminology or other words in the singular, plural, capitalization and/or he/she or they, are taken as interchangeable and therefore as referring to same.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            113 => 
            array (
                'id' => 114,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'Cookies',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            114 => 
            array (
                'id' => 115,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'We employ the use of cookies. By accessing SYZYGY, you agreed to use cookies in agreement with the SYZYGY\'s Privacy Policy.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            115 => 
            array (
                'id' => 116,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'Most interactive websites use cookies to let us retrieve the user’s details for each visit. Cookies are used by our website to enable the functionality of certain areas to make it easier for people visiting our website. Some of our affiliate/advertising partners may also use cookies.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            116 => 
            array (
                'id' => 117,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'License',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            117 => 
            array (
                'id' => 118,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'Please note that the system will show only as long as the any plan is active. As such, please make sure to renew the subscription to see your cards are active. Unless otherwise stated, SYZYGY and/or its licensors own the intellectual property rights for all material on this site. All intellectual property rights are reserved. You may access this from us for your own personal use subjected to restrictions set in these terms and conditions.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            118 => 
            array (
                'id' => 119,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'You must not:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            119 => 
            array (
                'id' => 120,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => '1. Republish material from this site
2. Reproduce, duplicate or copy material from this site',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            120 => 
            array (
                'id' => 121,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'This Agreement shall begin on the date hereof.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            121 => 
            array (
                'id' => 122,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'Parts of this website offer an opportunity for users to post and exchange opinions and information in certain areas of the website. SYZYGY does not filter, edit, publish or review Comments prior to their presence on the website. Comments do not reflect the views and opinions of SYZYGY, its agents and/or affiliates. Comments reflect the views and opinions of the person who post their views and opinions. To the extent permitted by applicable laws, SYZYGY shall not be liable for the Comments or for any liability, damages or expenses caused and/or suffered as a result of any use of and/or posting of and/or appearance of the Comments on this website.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            122 => 
            array (
                'id' => 123,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'SYZYGY reserves the right to monitor all Comments and to remove any Comments which can be considered inappropriate, offensive or causes breach of these Terms and Conditions.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            123 => 
            array (
                'id' => 124,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'You warrant and represent that:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            124 => 
            array (
                'id' => 125,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => '1. You are entitled to post the Comments on our website 
2. The Comments do not invade any intellectual property right, including without limitation copyright, patent or trademark of any third party;
3. The Comments do not contain any defamatory, libelous, offensive, indecent or otherwise unlawful material which is an invasion of privacy
4. The Comments will not be used to solicit or promote business or custom or present commercial activities or unlawful activity.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            125 => 
            array (
                'id' => 126,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'You hereby grant SYZYGY a non-exclusive license to use, reproduce, edit and authorize others to use, reproduce and edit any of your Comments in any and all forms, formats or media.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            126 => 
            array (
                'id' => 127,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'Hyperlinking to our Content',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            127 => 
            array (
                'id' => 128,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'The following organizations may link to our Website without prior written approval:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            128 => 
            array (
                'id' => 129,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => '1. Government agencies;
2. Search engines;
3. News organizations;
4. Online directory distributors may link to our Website in the same manner as they hyperlink to the Websites of other listed businesses; and
5. System wide Accredited Businesses except soliciting non-profit organizations, charity shopping malls, and charity fundraising groups which may not hyperlink to our Web site.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            129 => 
            array (
                'id' => 130,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
            'section_content' => 'These organizations may link to our home page, to publications or to other Website information so long as the link: (a) is not in any way deceptive; (b) does not falsely imply sponsorship, endorsement or approval of the linking party and its products and/or services; and (c) fits within the context of the linking party’s site.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            130 => 
            array (
                'id' => 131,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'We may consider and approve other link requests from the following types of organizations:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            131 => 
            array (
                'id' => 132,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => '1. commonly-known consumer and/or business information sources;
2. dot.com community sites;
3. associations or other groups representing charities;
4. online directory distributors;
5. internet portals;
6. accounting, law and consulting firms; and
7. educational institutions and trade associations.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            132 => 
            array (
                'id' => 133,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
            'section_content' => 'We will approve link requests from these organizations if we decide that: (a) the link would not make us look unfavorably to ourselves or to our accredited businesses; (b) the organization does not have any negative records with us; (c) the benefit to us from the visibility of the hyperlink compensates the absence of SYZYGY; and (d) the link is in the context of general resource information.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            133 => 
            array (
                'id' => 134,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
            'section_content' => 'These organizations may link to our home page so long as the link: (a) is not in any way deceptive; (b) does not falsely imply sponsorship, endorsement or approval of the linking party and its products or services; and (c) fits within the context of the linking party’s site.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            134 => 
            array (
                'id' => 135,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'If you are one of the organizations listed in paragraph 2 above and are interested in linking to our website, you must inform us by sending an e-mail to SYZYGY. Please include your name, your organization name, contact information as well as the URL of your site, a list of any URLs from which you intend to link to our Website, and a list of the URLs on our site to which you would like to link. Wait 2-3 weeks for a response.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            135 => 
            array (
                'id' => 136,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'Approved organizations may hyperlink to our Website as follows:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            136 => 
            array (
                'id' => 137,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => '1. By use of our corporate name; or
2. By use of the uniform resource locator being linked to; or
3. By use of any other description of our Website being linked to that makes sense within the context and format of content on the linking party’s site.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            137 => 
            array (
                'id' => 138,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'No use of SYZYGY logo or other artwork will be allowed for linking absent a trademark license agreement.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            138 => 
            array (
                'id' => 139,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'iFrames',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            139 => 
            array (
                'id' => 140,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'Without prior approval and written permission, you may not create frames around our Webpages that alter in any way the visual presentation or appearance of our Website.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            140 => 
            array (
                'id' => 141,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'Content Liability',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            141 => 
            array (
                'id' => 142,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
            'section_content' => 'We shall not be hold responsible for any content that appears on the sites. You agree to protect and defend us against all claims that is rising on your Website. No link(s) should appear on any Website that may be interpreted as libelous, obscene or criminal, or which infringes, otherwise violates, or advocates the infringement or other violation of, any third party rights.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            142 => 
            array (
                'id' => 143,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'Reservation of Rights',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            143 => 
            array (
                'id' => 144,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'We reserve the right to request that you remove all links or any particular link to our Website. You approve to immediately remove all links to our Website upon request. We also reserve the right to amend these terms and conditions and it’s linking policy at any time. By continuously linking to our Website, you agree to be bound to and follow these linking terms and conditions.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            144 => 
            array (
                'id' => 145,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'Removal of links from our website',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            145 => 
            array (
                'id' => 146,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'If you find any link on our Website that is offensive for any reason, you are free to contact and inform us any moment. We will consider requests to remove links but we are not obligated to or so or to respond to you directly.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            146 => 
            array (
                'id' => 147,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'We do not ensure that the information on this website is correct, we do not warrant its completeness or accuracy; nor do we promise to ensure that the website remains available or that the material on the website is kept up to date.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            147 => 
            array (
                'id' => 148,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_title',
                'section_content' => 'Disclaimer',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            148 => 
            array (
                'id' => 149,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'To the maximum extent permitted by applicable law, we exclude all representations, warranties and conditions relating to our website and the use of this website. Nothing in this disclaimer will:',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            149 => 
            array (
                'id' => 150,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => '1. limit or exclude our or your liability for death or personal injury;
2. limit or exclude our or your liability for fraud or fraudulent misrepresentation;
3. limit any of our or your liabilities in any way that is not permitted under applicable law; or
4. exclude any of our or your liabilities that may not be excluded under applicable law.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            150 => 
            array (
                'id' => 151,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
            'section_content' => 'The limitations and prohibitions of liability set in this Section and elsewhere in this disclaimer: (a) are subject to the preceding paragraph; and (b) govern all liabilities arising under the disclaimer, including liabilities arising in contract, in tort and for breach of statutory duty.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            151 => 
            array (
                'id' => 152,
                'page_name' => 'terms',
                'section_name' => 'terms',
                'section_title' => 'term_content_description',
                'section_content' => 'Wish to mention that we will not be liable for any loss or damage of any nature.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            152 => 
            array (
                'id' => 153,
                'page_name' => 'footer',
                'section_name' => 'footer',
                'section_title' => 'social-facebook',
                'section_content' => '1',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            153 => 
            array (
                'id' => 154,
                'page_name' => 'footer',
                'section_name' => 'footer',
                'section_title' => 'social-twitter',
                'section_content' => '2#',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            154 => 
            array (
                'id' => 155,
                'page_name' => 'footer',
                'section_name' => 'footer',
                'section_title' => 'social-instagram',
                'section_content' => '3#',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            155 => 
            array (
                'id' => 156,
                'page_name' => 'footer',
                'section_name' => 'footer',
                'section_title' => 'social-linkedIn',
                'section_content' => 'syzygysec@gmail.com',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            156 => 
            array (
                'id' => 157,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'refund-title',
                'section_content' => 'Return and Refund Policy',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            157 => 
            array (
                'id' => 158,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'refund-desc',
                'section_content' => 'Last updated: March 28, 2023',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            158 => 
            array (
                'id' => 159,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => 'Thank you for joining with SYZYGY Family.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            159 => 
            array (
                'id' => 160,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => 'If, for any reason, Please note the followings.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            160 => 
            array (
                'id' => 161,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => 'This is an intangible product, So we do not provide refund, once purchased.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            161 => 
            array (
                'id' => 162,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => 'So Return requirements will not be there.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            162 => 
            array (
                'id' => 163,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => 'We do not refund for any reason.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            163 => 
            array (
                'id' => 164,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => 'We have provided Free plan for you to satisfy, before opt in for long term plans.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            164 => 
            array (
                'id' => 165,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => 'As such,  You are kindly requested to be happy with our Free plan for your satisfaction before continuing.',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            165 => 
            array (
                'id' => 166,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            166 => 
            array (
                'id' => 167,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            167 => 
            array (
                'id' => 168,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            168 => 
            array (
                'id' => 169,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            169 => 
            array (
                'id' => 170,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            170 => 
            array (
                'id' => 171,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            171 => 
            array (
                'id' => 172,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            172 => 
            array (
                'id' => 173,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            173 => 
            array (
                'id' => 174,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            174 => 
            array (
                'id' => 175,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            175 => 
            array (
                'id' => 176,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            176 => 
            array (
                'id' => 177,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            177 => 
            array (
                'id' => 178,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            178 => 
            array (
                'id' => 179,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            179 => 
            array (
                'id' => 180,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            180 => 
            array (
                'id' => 181,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            181 => 
            array (
                'id' => 182,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            182 => 
            array (
                'id' => 183,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            183 => 
            array (
                'id' => 184,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            184 => 
            array (
                'id' => 185,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            185 => 
            array (
                'id' => 186,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            186 => 
            array (
                'id' => 187,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            187 => 
            array (
                'id' => 188,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            188 => 
            array (
                'id' => 189,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            189 => 
            array (
                'id' => 190,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            190 => 
            array (
                'id' => 191,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            191 => 
            array (
                'id' => 192,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            192 => 
            array (
                'id' => 193,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            193 => 
            array (
                'id' => 194,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            194 => 
            array (
                'id' => 195,
                'page_name' => 'refund',
                'section_name' => 'refund',
                'section_title' => 'desc',
                'section_content' => '',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2021-10-09 10:32:44',
            ),
            195 => 
            array (
                'id' => 196,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_title',
                'section_content' => 'About us',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            196 => 
            array (
                'id' => 197,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'Welcome to SYZYGY!',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            197 => 
            array (
                'id' => 198,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'Warmly welcome to enjoy the taste of our products.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            198 => 
            array (
                'id' => 199,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'We make our Clients happy by helping them to automate their Business Management with the software built with the latest technologies.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            199 => 
            array (
                'id' => 200,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'I am sure that you too could get the benefit of our products for your success.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            200 => 
            array (
                'id' => 201,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_title',
                'section_content' => 'About the company',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            201 => 
            array (
                'id' => 202,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'We are a Software development and Marketing Company in Sri Lanka, having many different Software products.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            202 => 
            array (
                'id' => 203,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
            'section_content' => 'Market Leader in the country for the Filling (Fuel / Gas) Stations Software with the latest technologies.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            203 => 
            array (
                'id' => 204,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'This Filling Station software is having many different modules to cater in to almost every segment of the Filling Station Management.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            204 => 
            array (
                'id' => 205,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'Further steps are underway to introduce our software for the other countries too.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            205 => 
            array (
                'id' => 206,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'Also, in the field of Digital Marketing such as E Mail Marketing, WhatsApp & SMS Marketing, social media Marketing etc.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            206 => 
            array (
                'id' => 207,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'In addition, we do provide SMS Marketing for the candidates in almost every election in Sri Lanka, to transmit their election messages.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            207 => 
            array (
                'id' => 208,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'We do have many specialized software for Cheque Writing, WhatsApp Bulk Marketing, Bulk SMS Marketing, Rice Mill operations, POS, Land Sales, Related to Personal health etc. Could build and customize software as per your need.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            208 => 
            array (
                'id' => 209,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => '.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            209 => 
            array (
                'id' => 210,
                'page_name' => 'about',
                'section_name' => 'about',
                'section_title' => 'about_content_description',
                'section_content' => 'Please Contact us with your requirements. Wish you a Happy Day',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            210 => 
            array (
                'id' => 211,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_name',
                'section_content' => 'Contact',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            211 => 
            array (
                'id' => 212,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_subtitle',
                'section_content' => 'Got any question? Let’s talk about it.',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            212 => 
            array (
                'id' => 213,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_section_1',
                'section_content' => 'Office',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            213 => 
            array (
                'id' => 214,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_section_1_content_1',
                'section_content' => 'Malabe',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            214 => 
            array (
                'id' => 215,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_section_1_content_2',
                'section_content' => 'Sri Lanka',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            215 => 
            array (
                'id' => 216,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_section_2',
                'section_content' => 'Contacts',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            216 => 
            array (
                'id' => 217,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_section_2_content_1',
                'section_content' => 'syzygysec@gmail.com',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            217 => 
            array (
                'id' => 218,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_section_2_content_1',
            'section_content' => '+(94) 77 4055 434 / 71 1616 191',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
            218 => 
            array (
                'id' => 219,
                'page_name' => 'contact',
                'section_name' => 'contact',
                'section_title' => 'page_section_3',
                'section_content' => 'Socials',
                'created_at' => '2022-08-27 03:51:02',
                'updated_at' => '2022-08-27 03:51:02',
            ),
        ));
        
        
    }
}