<?php

/*
 * Administration functions for PhreeBooks module
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.TXT.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Bizuno to newer
 * versions in the future. If you wish to customize Bizuno for your
 * needs please refer to http://www.phreesoft.com for more information.
 *
 * @name       Bizuno ERP
 * @author     Dave Premo, PhreeSoft <support@phreesoft.com>
 * @copyright  2008-2018, PhreeSoft
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @version    2.x Last Update: 2018-04-10
 * @filesource /lib/controller/module/phreebooks/admin.php
 */

namespace bizuno;

require_once(BIZUNO_LIB . "controller/module/phreebooks/functions.php");

class phreebooksAdmin {

    public $moduleID = 'phreebooks';

    function __construct() {
        $this->lang = getLang($this->moduleID);
        $this->totalMethods = ['balance', 'balanceBeg', 'balanceEnd', 'debitcredit', 'discountChk', 'subtotal', 'subtotalChk', 'tax_item', 'total'];
        $values = getModuleCache('phreebooks', 'chart', 'defaults', getUserCache('profile', 'currency', false, 'USD'));
        $this->glDefaults = [
            'cash'       => isset($values[0]) ? $values[0] : '',
            'receivables'=> isset($values[2]) ? $values[2] : '',
            'inventory'  => isset($values[4]) ? $values[4] : '',
            'payables'   => isset($values[20])? $values[20]: '',
            'liability'  => isset($values[22])? $values[22]: '',
            'sales'      => isset($values[30])? $values[30]: '',
            'expense'    => isset($values[34])? $values[34]: ''];
        $this->assets    = [0, 2, 4, 6, 8, 12, 32, 34]; // gl_account types that are assets
		$this->settings  = array_replace_recursive(getStructureValues($this->settingsStructure()), getModuleCache($this->moduleID, 'settings', false, false, []));
        $this->structure = [
            'url' => BIZUNO_URL."controller/module/$this->moduleID/",
            'version' => MODULE_BIZUNO_VERSION,
            'category' => 'bizuno',
            'required' => '1',
            'dirMethods' => 'totals',
            'attachPath' => 'data/phreebooks/uploads/',
//			'totals'       => getModuleCache('phreebooks', 'totals', false, false, $this->totalsStructure()),
            'menuBar' => ['child'=>[
                'banking' => ['order'=>40,'label'=>lang('banking'),'group'=>'bnk','icon'=>'bank','events'=>['onClick'=>"hrefClick('bizuno/main/bizunoHome&menuID=banking');"],'child'=>[
                    'j17_mgr' => ['order'=>70,'label'=>lang('journal_main_journal_id_17'),'icon'=>'payment',   'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=17');"]],
                    'j18_mgr' => ['order'=>10,'label'=>lang('journal_main_journal_id_18'),'icon'=>'payment',   'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=18');"]],
                    'j20_bulk'=> ['order'=>55,'label'=>lang('phreebooks_manager_bulk'),   'icon'=>'bank-check','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=20&bizAction=bulk');"]],
                    'j20_mgr' => ['order'=>50,'label'=>lang('journal_main_journal_id_20'),'icon'=>'bank-check','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=20');"]],
                    'j22_mgr' => ['order'=>20,'label'=>lang('journal_main_journal_id_22'),'icon'=>'bank-check','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=22');"]],
                    'register'=> ['order'=>80,'label'=>lang('phreebooks_register'),       'icon'=>'register',  'events'=>['onClick'=>"hrefClick('phreebooks/register/manager');"]],
                    'recon'   => ['order'=>85,'label'=>lang('phreebooks_recon'),          'icon'=>'apply',     'events'=>['onClick'=>"hrefClick('phreebooks/reconcile/manager');"]],
                    'rpt_bank'=> ['order'=>99,'label'=>lang('reports'),                   'icon'=>'mimeDoc',   'events'=>['onClick'=>"hrefClick('phreeform/main/manager&gID=bnk');"]]]],
                'customers' => ['child'=>[
                    'sales' => ['order'=>45,'label'=>lang('journal_main_journal_id_12_mgr'),'icon'=>'sales','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=12&mgr=1');"],'child'=>[
                        'j9_mgr' => ['order'=>30,'label'=>lang('journal_main_journal_id_9'), 'icon'=>'quote', 'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=9');"]],
                        'j10_mgr'=> ['order'=>20,'label'=>lang('journal_main_journal_id_10'),'icon'=>'order', 'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=10');"]],
                        'j12_mgr'=> ['order'=>10,'label'=>lang('journal_main_journal_id_12'),'icon'=>'sales', 'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=12');"]],
                        'j13_mgr'=> ['order'=>40,'label'=>lang('journal_main_journal_id_13'),'icon'=>'credit','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=13');"]]]]]],
                'inventory' => ['child'=>[
                    'j14_mgr' => ['order'=>35,'label'=>lang('journal_main_journal_id_14'),'icon'=>'tools',  'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=14');"]],
                    'j16_mgr' => ['order'=>50,'label'=>lang('journal_main_journal_id_16'),'icon'=>'inv-adj','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=16');"]]]],
                'ledger' => ['order'=>50,'label'=>lang('general_ledger'),'group'=>'gl','icon'=>'journal','events'=>['onClick'=>"hrefClick('bizuno/main/bizunoHome&menuID=ledger');"],'child'=>[
                    'j2_mgr'  => ['order'=>10, 'label'=>lang('journal_main_journal_id_2'),'icon'=>'journal','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=2');"]],
//    				'cashflow'=> ['order'=>80,'label'=>lang('cash_flow')),       'icon'=>'linechart','events'=>['onClick'=>"hrefClick('phreebooks/budget/cashFlow');"]],
                    'budget'  => ['order'=>90,'label'=>lang('phreebooks_budget'),'icon'=>'budget',   'events'=>['onClick'=>"hrefClick('phreebooks/budget/manager');"]],
                    'rpt_jrnl'=> ['order'=>99,'label'=>lang('reports'),          'icon'=>'mimeDoc',  'events'=>['onClick'=>"hrefClick('phreeform/main/manager&gID=gl');"]]]],
                'tools' => ['child'=>[
                    'j0_mgr'  => ['order'=>75,'label'=>lang('journal_main_journal_id_0'),'icon'=>'search','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=0');"]]]],
                'vendors' => ['child'=>[
                    'purch' => ['order'=>45,'label'=>lang('journal_main_journal_id_6_mgr'), 'icon'=>'purchase','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=6&mgr=1');"],'child'=>[
                        'j3_mgr' => ['order'=>30,'label'=>lang('journal_main_journal_id_3'),'icon'=>'quote',   'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=3');"]],
                        'j4_mgr' => ['order'=>20,'label'=>lang('journal_main_journal_id_4'),'icon'=>'order',   'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=4');"]],
                        'j6_mgr' => ['order'=>10,'label'=>lang('journal_main_journal_id_6'),'icon'=>'purchase','events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=6');"]],
                        'j7_mgr' => ['order'=>40,'label'=>lang('journal_main_journal_id_7'),'icon'=>'credit',  'events'=>['onClick'=>"hrefClick('phreebooks/main/manager&jID=7');"]]]]]]]],
            'hooks' => ['bizuno' => [
                    'admin' => [
                        'loadBrowserSession' => ['page' => 'admin', 'class' => 'phreebooksAdmin', 'order' => 50]],
                    'roles' => [
                        'edit' => ['order' => 50, 'page' => 'admin', 'class' => 'phreebooksAdmin', 'method' => 'rolesEdit'],
                        'save' => ['order' => 50, 'page' => 'admin', 'class' => 'phreebooksAdmin', 'method' => 'rolesSave']],
                    'users' => [
                        'edit' => ['order' => 50, 'page' => 'admin', 'class' => 'phreebooksAdmin', 'method' => 'usersEdit'],
                        'save' => ['order' => 50, 'page' => 'admin', 'class' => 'phreebooksAdmin', 'method' => 'usersSave']]]],
            'api' => ['path' => 'phreebooks/api/journalAPI', 'attr' => ['jID' => 12]]]; // default to import sales
        $this->phreeformProcessing = [
            'subTotal'  => ['text'=>lang('subtotal'),             'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'invBalance'=> ['text'=>lang('balance'),              'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'invRefNum' => ['text'=>lang('journal_main_invoice_num_2'),'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'bnkReg'    => ['text'=>lang('bank_register_format'), 'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'pmtDate'   => ['text'=>lang('payment_due_date'),     'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'pmtDisc'   => ['text'=>lang('payment_discount'),     'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'paymentDue'=> ['text'=>lang('payment_due'),          'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'paymentRcv'=> ['text'=>lang('payment_received'),     'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'paymentRef'=> ['text'=>lang('payment_reference'),    'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'ship_bal'  => ['text'=>lang('shipped_balance'),      'group'=>$this->lang['title'],'module'=>'bizuno','function'=> 'viewFormat'],
            'shipBalVal'=> ['text'=>lang('shipped_balance_value'),'group'=>$this->lang['title'],'module'=>'bizuno','function'=> 'viewFormat'],
            'ship_prior'=> ['text'=>lang('shipped_prior'),        'group'=>$this->lang['title'],'module'=>'bizuno','function'=> 'viewFormat'],
            'rep_id'    => ['text'=>lang('contacts_rep_id_c'),    'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'taxTitle'  => ['text'=>lang('tax_rates_title'),      'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'terms'     => ['text'=>lang('terms')." (".lang('customers').")",'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'terms_v'   => ['text'=>lang('terms')." (".lang('vendors').")",  'group'=>$this->lang['title'],'module'=>'bizuno','function'=>'viewFormat'],
            'age_00'    => ['text'=>$this->lang['pb_gl_age_00'],  'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'age_30'    => ['text'=>$this->lang['pb_gl_age_30'],  'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'age_60'    => ['text'=>$this->lang['pb_gl_age_60'],  'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'age_90'    => ['text'=>$this->lang['pb_gl_age_90'],  'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'begBal'    => ['text'=>lang('beginning_balance'),    'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'endBal'    => ['text'=>lang('ending_balance'),       'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isCur'     => ['text'=>lang('gl_acct_type_30'),      'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isYtd'     => ['text'=>$this->lang['pb_is_ytd'],     'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isBdgt'    => ['text'=>lang('budget'),               'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isBytd'    => ['text'=>$this->lang['pb_is_budget_ytd'],'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isLcur'    => ['text'=>$this->lang['ly_actual'],     'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isLytd'    => ['text'=>$this->lang['pb_is_last_ytd'],'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isLBgt'    => ['text'=>$this->lang['ly_budget'],     'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks'],
            'isLBtd'    => ['text'=>$this->lang['pb_is_last_budget_ytd'],'group'=>$this->lang['title'],'module'=>$this->moduleID,'function'=>'processPhreeBooks']];
        $this->phreeformFormatting = [
            'j_desc' => ['text' => lang('journal_main_journal_id'), 'group' => $this->lang['title'], 'module' => 'bizuno', 'function' => 'viewFormat'],
            'glType' => ['text' => lang('gl_acct_type'), 'group' => $this->lang['title'], 'module' => 'bizuno', 'function' => 'viewFormat'],
            'glTitle' => ['text' => lang('gl_acct_title'), 'group' => $this->lang['title'], 'module' => 'bizuno', 'function' => 'viewFormat']];
        $this->notes = [$this->lang['note_phreebooks_install_1'],$this->lang['note_phreebooks_install_2'],$this->lang['note_phreebooks_install_3']];
    }

    /**
     * Sets the user defined settings structure
     * @return array - structure used in the main settings tab
     */
    public function settingsStructure() {
        $noYes = [['id' => '0', 'text' => lang('no')], ['id' => '1', 'text' => lang('yes')]];
        $data = [
            'general' => [
                'round_tax_auth' => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '0']],
                'shipping_taxed' => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '0']],
                'isolate_stores' => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '0']]],
            'customers' => [
                'gl_receivables' => ['jsBody' => htmlComboGL('customers_gl_receivables'), 'attr' => ['value' => $this->glDefaults['receivables']]],
                'gl_sales'       => ['jsBody' => htmlComboGL('customers_gl_sales'), 'attr' => ['value' => $this->glDefaults['sales']]],
                'gl_cash'        => ['jsBody' => htmlComboGL('customers_gl_cash'), 'attr' => ['value' => $this->glDefaults['cash']]],
                'gl_discount'    => ['jsBody' => htmlComboGL('customers_gl_discount'), 'attr' => ['value' => $this->glDefaults['sales']]],
                'gl_deposit_cash'=> ['jsBody' => htmlComboGL('customers_gl_deposit_cash'), 'attr' => ['value' => $this->glDefaults['cash']]],
                'gl_liability'   => ['jsBody' => htmlComboGL('customers_gl_liability'), 'attr' => ['value' => $this->glDefaults['liability']]],
                'gl_expense'     => ['jsBody' => htmlComboGL('customers_gl_expense'), 'attr' => ['value' => $this->glDefaults['expense']]],
                'terms'          => ['attr' => ['type' => 'hidden', 'value' => '2']],
                'terms_edit'     => ['icon' => 'settings', 'size' => 'small', 'label' => lang('terms'), 'attr' => ['type' => 'hidden'], 'events' => ['onClick' => "jsonAction('contacts/main/editTerms&type=c&callBack=customers_terms', 0, jq('#customers_terms').val());"]],
                'terms_text'     => ['attr' => ['value' => '']],
                'auto_add'       => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '1']],
                'show_status'    => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '1']],
                'include_all'    => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '0']]],
            'vendors' => [
                'gl_payables'    => ['jsBody' => htmlComboGL('vendors_gl_payables'), 'attr' => ['value' => $this->glDefaults['payables']]],
                'gl_purchases'   => ['jsBody' => htmlComboGL('vendors_gl_purchases'), 'attr' => ['value' => $this->glDefaults['inventory']]],
                'gl_cash'        => ['jsBody' => htmlComboGL('vendors_gl_cash'), 'attr' => ['value' => $this->glDefaults['cash']]],
                'gl_discount'    => ['jsBody' => htmlComboGL('vendors_gl_discount'), 'attr' => ['value' => $this->glDefaults['payables']]],
                'gl_deposit_cash'=> ['jsBody' => htmlComboGL('vendors_gl_deposit_cash'), 'attr' => ['value' => $this->glDefaults['cash']]],
                'gl_liability'   => ['jsBody' => htmlComboGL('vendors_gl_liability'), 'attr' => ['value' => $this->glDefaults['liability']]],
                'gl_expense'     => ['jsBody' => htmlComboGL('vendors_gl_expense'), 'attr' => ['value' => $this->glDefaults['expense']]],
                'terms'          => ['attr' => ['type' => 'hidden', 'value' => '3:0:0:30:1000.00']],
                'terms_edit'     => ['icon' => 'settings', 'size' => 'small', 'label' => lang('terms'), 'attr' => ['type' => 'hidden'], 'events' => ['onClick' => "jsonAction('contacts/main/editTerms&type=v&callBack=vendors_terms', 0, jq('#vendors_terms').val());"]],
                'terms_text'     => ['attr' => ['value' => '']],
                'auto_add'       => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '1']],
                'show_status'    => ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '1']]],
        ];
        if (sizeof(getModuleCache('phreebooks', 'currency', 'iso')) > 1) {
            $data['general']['auto_currency'] = ['values' => $noYes, 'attr' => ['type' => 'select', 'value' => '1']];
        }
        settingsFill($data, $this->moduleID);
//        if (isset($settings['customers']['terms'])){ $data['customers']['terms_text']['attr']['value']= viewTerms($data['customers']['terms']['attr']['value']); }
//        if (isset($settings['vendors']['terms']))  { $data['vendors']['terms_text']['attr']['value']  = viewTerms($data['vendors']['terms']['attr']['value']); }
        return $data;
    }

    /**
     * Special initialization actions set during first startup and cache creation
     * @return boolean true
     */
    public function initialize() {
        periodAutoUpdate();
/*      if (getModuleCache('phreebooks', 'settings', 'general', 'auto_currency')) {
            require_once(BIZUNO_LIB . "controller/module/phreebooks/currency.php");
            $curr = new phreebooksCurrency();
            $curr->update();
        }*/
        return true; // successful
    }

    /**
     * User settings main entry page
     * @param array $layout - structure coming in
     * @return modified $layout
     */
    public function adminHome(&$layout = []) {
        if (!$security = validateSecurity('bizuno', 'admin', 1)) { return; }
        require_once(BIZUNO_LIB . "controller/module/phreebooks/currency.php");
        $currency = new phreebooksCurrency();
        $data = [
            'tabs' => ['tabAdmin' => ['divs' => [
                'settings'   => ['order' => 10, 'label' => lang('settings'), 'src' => BIZUNO_LIB . "view/module/bizuno/tabAdminSettings.php"],
                'tabGL'      => ['order' => 20, 'label' => lang('phreebooks_chart_of_accts'), 'type' => 'html', 'html' => '',
                    'attr' => ["data-options" => "href:'" . BIZUNO_AJAX . "&p=phreebooks/chart/manager'"]],
                'tabCurrency'=> ['order' => 30, 'label' => lang('currencies'), 'type' => 'html', 'html' => '',
                    'attr' => ["data-options" => "href:'" . BIZUNO_AJAX . "&p=phreebooks/currency/manager'"]],
                'tabTaxc'    => ['order' => 40, 'label' => pullTableLabel('inventory', 'tax_rate_id', 'c'), 'type' => 'html', 'html' => '',
                    'attr' => ["data-options" => "href:'" . BIZUNO_AJAX . "&p=phreebooks/tax/manager&type=c'"]],
                'tabTaxv'    => ['order' => 50, 'label' => pullTableLabel('inventory', 'tax_rate_id', 'v'), 'type' => 'html', 'html' => '',
                    'attr' => ["data-options" => "href:'" . BIZUNO_AJAX . "&p=phreebooks/tax/manager&type=v'"]],
                'tabTotals'  => ['order' => 60, 'label' => lang('totals'), 'attr' => ['module' => $this->moduleID, 'type' => $this->structure['dirMethods']], 'src'=>BIZUNO_LIB."view/module/bizuno/tabAdminMethods.php"],
                'tabDBs'     => ['order' => 70, 'label' => lang('dashboards'), 'attr'=>['module'=>$this->moduleID, 'type'=>'dashboards'], 'src' => BIZUNO_LIB."view/module/bizuno/tabAdminMethods.php"],
                'tabFY'      => ['order' => 80, 'label' => lang('fiscal_calendar'), 'type'=>'html', 'html'=>'',
                    'attr' => ["data-options" => "href:'" . BIZUNO_AJAX . "&p=phreebooks/admin/managerFY'"]],
                'tabTools'   => ['order' => 90, 'label' => $this->lang['journal_tools'],'src'=>BIZUNO_LIB."view/module/phreebooks/tabJournalTools.php"]]]],
            'datagrid' => ['dgCurrency' => $currency->dgCurrency('dgCurrency', $security)],
            'form' => ['frmCurrency' => ['attr' => ['type' => 'form', 'action' => BIZUNO_AJAX."&p=phreebooks/currency/save"]]],
            'fields' => [
                // General Journal
                'repost' => ['position' => 'after', 'attr' => ['type' => 'checkbox']], // label comes later
                'repost_begin' => ['attr' => ['type' => 'date', 'value' => date('Y-m-d')]],
                'repost_end' => ['attr' => ['type' => 'date', 'value' => date('Y-m-d')]],
                'btn_repost' => ['icon' => 'save', 'size' => 'large', 'events' => ['onClick' => "divSubmit('phreebooks/tools/glRepostBulk', 'glRepost');"]],
                // Misc tools
                'btnRepairGL' => ['attr' => ['type' => 'button', 'value' => lang('start')], 'events' => ['onClick' => "jsonAction('phreebooks/tools/glRepair');"]],
                'btnPruneCogs' => ['attr' => ['type' => 'button', 'value' => lang('start')], 'events' => ['onClick' => "jsonAction('phreebooks/tools/pruneCogs');"]],
                'purge_db' => ['styles' => ["text-align" => "right"], 'attr' => ['size' => "7"]],
                'btn_purge' => ['attr' => ['type' => 'button', 'value' => $this->lang['phreebooks_purge_db_journal']],
                    'events' => ['onClick' => "if (confirm('" . $this->lang['msg_gl_db_purge_confirm'] . "')) jsonAction('phreebooks/tools/glPurge', 0, jq('#purge_db').val());"]],
                'dateAtchCln' => ['label' => lang(''), 'classes' => ['easyui-datebox'], 'attr' => ['value' => viewDate(localeCalculateDate(date('Y-m-d'), 0, -3))]],
                'btnAtchCln' => ['attr' => ['type' => 'button', 'value' => lang('start')],
                    'events' => ['onClick' => "if (confirm('" . $this->lang['pb_attach_clean_confirm'] . "')) jsonAction('phreebooks/tools/cleanAttach', 0, jq('#dateAtchCln').datebox('getValue'));"]],
            ],
            'javascript' => ['dataCurrency' => "var dataCurrency = " . json_encode(array_values(getModuleCache('phreebooks', 'currency', 'iso'))) . ";\n"],
            'lang' => $this->lang,
        ];
        $layout = array_replace_recursive($layout, adminStructure($this->moduleID, $this->settingsStructure(), $this->lang), $data);
    }

    /**
     * Saves the user defined settings
     */
    public function adminSave() {
        readModuleSettings($this->moduleID, $this->settings);
    }

    public function managerFY(&$layout=[])
    {
        $fy = clean('fy', ['format'=>'integer', 'default'=>getModuleCache('phreebooks', 'fy', 'fiscal_year', false, date('Y'))], 'get');
        $dbMaxFY= dbGetValue(BIZUNO_DB_PREFIX . "journal_periods", ["MAX(fiscal_year) AS fiscal_year", "MAX(period) AS period"], false, false);
        $maxFY  = $dbMaxFY['fiscal_year'] > 0 ? $dbMaxFY['fiscal_year'] : 0;
        $FYs    = [];
        $stmt   = dbGetResult("SELECT DISTINCT fiscal_year FROM ".BIZUNO_DB_PREFIX."journal_periods");
        $dbFYs  = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($dbFYs as $row) { $FYs[] = ['id' => $row['fiscal_year'], 'text' => $row['fiscal_year']]; }
        $dbPer  = dbGetMulti(BIZUNO_DB_PREFIX . "journal_periods", "fiscal_year=$fy", "period");
        $periods= [];
        foreach ($dbPer as $row) { $periods[$row['period']] = ['start' => $row['start_date'], 'end' => $row['end_date']]; }
		$layout = array_replace_recursive($layout,  ['type'=>'divHTML',
			'divs' => ['divFY' => ['order' => 80, 'src'=>BIZUNO_LIB."view/module/phreebooks/tabToolsFY.php"]],
            'fields' => [
                'fy' => ['label' => lang('phreebooks_fiscal_year'), 'values' => $FYs, 'attr' => ['type' => 'select', 'value' => $fy],
                    'events' => ['onChange' => "
var tab=jq('#tabAdmin').tabs('getSelected'); tab.panel( 'refresh', '".BIZUNO_AJAX."&p=phreebooks/admin/managerFY&fy='+jq('#fy').val() );"]],
                'btnSaveFy' => ['icon' => 'save', 'size' => 'large', 'events' => ['onClick' => "divSubmit('phreebooks/tools/fySave', 'fyCal');"]],
                'btnNewFy' => ['attr' => ['type' => 'button', 'value' => $this->lang['phreebooks_new_fiscal_year']],
                    'events' => ['onClick' => "if (confirm('" . sprintf($this->lang['msg_gl_fiscal_year_confirm'], $maxFY + 1) . "')) { jq('body').addClass('loading'); jsonAction('phreebooks/tools/fyAdd'); }"]],
                'btnCloseFy' => ['attr' => ['type' => 'button', 'value' => $this->lang['del_fiscal_year_btn']],
                    'events' => ['onClick' => "jsonAction('phreebooks/tools/fyCloseValidate');"]]],
            'values' => [
                'periods' => $periods,
                'max_posted' => dbGetValue(BIZUNO_DB_PREFIX . "journal_main", "MAX(period) AS period", false, false)],
            'lang' => $this->lang]);
    }
    
    /**
     * Operations that need to be completed when first installing Bizuno for the PhreeBooks module
     */
    public function installFirst() {
        require_once(BIZUNO_LIB . "controller/module/phreebooks/currency.php");
        require_once(BIZUNO_LIB . "controller/module/phreebooks/chart.php");
        $cur = new phreebooksCurrency();
        $coa = new phreebooksChart();
        msgDebug("\n  Loading chart of accounts");
        $coa->chartInstall(getUserCache('profile', 'chart'));
        // set the currencies (should only be one at this time)
        $iso = getUserCache('profile', 'currency', false, 'USD');
        setModuleCache('phreebooks', 'currency', false, ['default' => $iso, 'iso' => [$iso => $cur->currencySettings($iso)]]);
        msgDebug("\n  Building fiscal year.");
        $current_year = date('Y');
        $start_year = getUserCache('profile', 'first_fy');
        $start_period = 1;
        $runaway = 0;
        while ($start_year <= $current_year) {
            setNewFiscalYear($start_year, $start_period, "$start_year-01-01");
            $start_year++;
            $start_period = $start_period + 12;
            $runaway++;
            if ($runaway > 10) { break; }
        }
        msgDebug("\n  Building and checking chart history");
        buildChartOfAccountsHistory();
        msgDebug("\n  Updating current period");
        setModuleCache('phreebooks', 'fy', false, ['period' => '99', 'period_start' => '2099-12-01', 'period_end' => '2099-12-31', 'fiscal_year' => '2099']);
        clearUserCache('profile', 'chart');
        clearUserCache('profile', 'first_fy');
        periodAutoUpdate(false);
    }

    /**
     * Installs the total methods at first install, can be modified by user later
     * @param array $layout - structure coming in
     * @return modified $layout
     */
    public function install(&$layout = []) {
        $bAdmin = new bizunoSettings();
        foreach ($this->totalMethods as $method) {
            $bAdmin->methodInstall($layout, ['module' => 'phreebooks', 'type' => 'totals', 'method' => $method], false);
        }
    }

    /**
     * Operations needed to build the browser cache at first log in specific to PhreeBooks module
     * @param array $layout - structure coming in
     * @return modified $layout
     */
    public function loadBrowserSession(&$layout = []) {
        $accts = []; // load gl Accounts
        foreach (getModuleCache('phreebooks', 'chart', 'accounts') as $row) {
            $row['asset'] = in_array($row['type'], $this->assets) ? 1 : 0;
            $row['type'] = viewFormat($row['type'], 'glType');
//			if (!isset($row['inactive']) || $row['inactive']=='0') $accts[] = $row; // doesn't allow for edit of GL Accounts
            $accts[] = $row; // need to remove keys
        }
        $layout['content']['dictionary']    = array_merge($layout['content']['dictionary'], $this->getBrowserLang());
        $layout['content']['glAccounts']    = ['total'=>sizeof($accts),  'rows'=>$accts];
        $cRates = loadTaxes('c');
        $layout['content']['taxRates']['c'] = ['total'=>sizeof($cRates), 'rows'=>$cRates];
        $vRates = loadTaxes('v');
        $layout['content']['taxRates']['v'] = ['total'=>sizeof($vRates), 'rows'=>$vRates];
        msgDebug("\nSending back data = " . print_r($layout['content'], true));
    }
    
    private function getBrowserLang()
    {
        return [
            'PB_INVOICE_RQD'     => $this->lang['msg_invoice_rqd'],
            'PB_INVOICE_WAITING' => $this->lang['msg_inv_waiting'],
            'PB_NEG_STOCK'       => $this->lang['msg_negative_stock'],
            'PB_RECUR_EDIT'      => $this->lang['msg_recur_edit'],
            'PB_SAVE_AS_CLOSED'  => $this->lang['msg_save_as_closed'],
            'PB_SAVE_AS_LINKED'  => $this->lang['msg_save_as_linked'],
            'PB_GL_ASSET_INC'    => $this->lang['bal_increase'],
            'PB_GL_ASSET_DEC'    => $this->lang['bal_decrease'],
            'PB_DBT_CRT_NOT_ZERO'=> $this->lang['err_debits_credits_not_zero']];
    }

    /**
     * Extends the Roles - Edit - PhreeBooks tab to add Sales and Purchase access
     * @param array $layout - structure coming in
     * @return modified $layout
     */
    public function rolesEdit(&$layout) {
        $rID = clean('rID', 'integer', 'get');
        $settings = json_decode(dbGetValue(BIZUNO_DB_PREFIX . "roles", 'settings', "id=$rID"), true);
        $enableSales = isset($settings['bizuno']['roles']['sales']) && $settings['bizuno']['roles']['sales'] ? true : false;
        $enablePurch = isset($settings['bizuno']['roles']['purch']) && $settings['bizuno']['roles']['purch'] ? true : false;
        $fields = [
            'phreebooks_sales' => ['label' => lang('journal_main_journal_id_12'), 'position' => 'after', 'attr' => ['type' => 'checkbox', 'value' => '1', 'checked' => $enableSales]],
            'phreebooks_purch' => ['label' => lang('journal_main_journal_id_6'), 'position' => 'after', 'attr' => ['type' => 'checkbox', 'value' => '1', 'checked' => $enablePurch]]];
        $output = "  <fieldset>\n<p>" . $this->lang['msg_pb_admin_roles'] . "</p>";
        $output .= html5('phreebooks_sales', $fields['phreebooks_sales']) . "<br />\n";
        $output .= html5('phreebooks_purch', $fields['phreebooks_purch']) . "<br />\n</fieldset>\n";
        if (!empty($layout['tabs']['tabRoles']['divs'])) {
            $layout['tabs']['tabRoles']['divs'][$this->moduleID]['html'] = $output . $layout['tabs']['tabRoles']['divs'][$this->moduleID]['html'];            
        }
    }

    /**
     * Extends the Roles settings to Save the PhereeBooks Specific settings
     * @return boolean null
     */
    public function rolesSave() {
        $rID = clean('id', 'integer', 'post');
        if (!$rID) {
            return;
        }
        if (!$security = validateSecurity('bizuno', 'roles', $rID ? 3 : 2)) {
            return;
        }
        $settings = json_decode(dbGetValue(BIZUNO_DB_PREFIX . "roles", 'settings', "id=$rID"), true);
        unset($settings[$this->moduleID]);
        $settings['bizuno']['roles']['sales'] = clean('phreebooks_sales', 'boolean', 'post');
        $settings['bizuno']['roles']['purch'] = clean('phreebooks_purch', 'boolean', 'post');
        dbWrite(BIZUNO_DB_PREFIX . "roles", ['settings' => json_encode($settings)], 'update', "id=$rID");
    }

    /**
     * Extends the users editor for PhreeBooks specific fields
     * @param array $layout - structure coming in
     * @return modified $layout
     */
    public function usersEdit(&$layout) {
        $layout['tabs']['tabUsers']['divs']['phreebooks'] = ['order' => 50, 'label' => $this->lang['title'], 'src' => BIZUNO_LIB . "view/module/phreebooks/tabUsersSettings.php"];
        $layout['fields']['restrict_period'] = ['label' => $this->lang['restrict_period'], 'position' => 'after', 'attr' => ['type' => 'checkbox']];
        if (!isset($layout['settings']['cash_acct']) || !$layout['settings']['cash_acct']) {
            $layout['settings']['cash_acct'] = getModuleCache('phreebooks', 'settings', 'customers', 'gl_cash');
        }
        $layout['fields']['cash_acct'] = ['label' => $this->lang['set_gl_cash'], 'position' => 'after',
            'js' => htmlComboGL('cash_acct'), 'attr' => ['value' => $layout['settings']['cash_acct']]];
        if (!isset($layout['settings']['ar_acct']) || !$layout['settings']['ar_acct']) {
            $layout['settings']['ar_acct'] = getModuleCache('phreebooks', 'settings', 'customers', 'gl_receivables');
        }
        $layout['fields']['ar_acct'] = ['label' => $this->lang['set_gl_receivables'], 'position' => 'after',
            'js' => htmlComboGL('ar_acct'), 'attr' => ['value' => $layout['settings']['ar_acct']]];
        if (!isset($layout['settings']['ap_acct']) || !$layout['settings']['ap_acct']) {
            $layout['settings']['ap_acct'] = getModuleCache('phreebooks', 'settings', 'vendors', 'gl_payables');
        }
        $layout['fields']['ap_acct'] = ['label' => $this->lang['set_gl_purchases'], 'position' => 'after',
            'js' => htmlComboGL('ap_acct'), 'attr' => ['value' => $layout['settings']['ap_acct']]];
    }

    /**
     * Extends the users save method with PHreeBooks specific fields
     * @return boolean null
     */
    public function usersSave() {
        $rID = clean('admin_id', 'integer', 'post');
        if (!$security = validateSecurity('bizuno', 'users', $rID ? 3 : 2)) {
            return;
        }
        if (!$rID) {
            return;
        }
        $settings = json_decode(dbGetValue(BIZUNO_DB_PREFIX . "users", 'settings', "admin_id=$rID"), true);
        $settings['restrict_period'] = clean('restrict_period', 'boolean', 'post');
        $settings['cash_acct'] = clean('cash_acct', 'text', 'post');
        $settings['ar_acct'] = clean('ar_acct', 'text', 'post');
        $settings['ap_acct'] = clean('ap_acct', 'text', 'post');
        dbWrite(BIZUNO_DB_PREFIX . "users", ['settings' => json_encode($settings)], 'update', "admin_id=$rID");
    }

    /**
     * Saves the users preferred order total sequence and methods used to set the order screen totals fields
     * @return null, but session and registry are updated
     */
    public function orderTotals() {
        $data = clean('data', 'text', 'get');
        if (!$data) {
            return msgAdd("Bad values sent!");
        }
        $vals = explode(';', $data);
        $output = [];
        foreach ($vals as $method) {
            $parts = explode(':', $method);
            $idx = array_shift($parts);
            $output[$idx] = [];
            $order = 1;
            foreach ($parts as $val) {
                if ($val) {
                    $output[$idx][] = ['name' => $val, 'order' => $order];
                } // 'path'=>$path_if_not_in_phreebooks /totals folder
                $order++;
            }
        }
        setModuleCache('phreebooks', 'totals', false, $output);
        msgAdd(lang('msg_settings_saved'), 'success');
    }

}
