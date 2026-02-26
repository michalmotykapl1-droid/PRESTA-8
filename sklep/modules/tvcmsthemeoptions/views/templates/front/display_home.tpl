{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License 3.0 (AFL-3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    {if Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW')}
    <div class="tvcmstheme-control">
        <div class="tvtheme-control">
            <form>
                <div class="tvthemecontrol-heading">
                    {* <div class="tvtheme-control-title-name">
                        {hook h='displayFrontSetting'}
                    </div>
                    <div class="tvtheme-control-title-name-reset-btn">
                        <p>{l s='THEME OPTION' d='Shop.Theme.Global'}</p>
                    </div> *}
                    <div class="tvcms-custom-font-1"></div>
                    <div class="tvcms-custom-font-2"></div>
                    <div class="tvcms-custom-color"></div>
                    <div class="tvcms-custom-theme"></div>
                </div>
                <div class="tvtheme-control-wrapper">
                    <table>
                        <tr class="tvselect-theme tvall-theme-content">
                            <td>
                                <div class="tvselect-theme-name">{l s='Theme Color' d='Shop.Theme.Global'}</div>
                                {* <select class="tvselect-theme-select" id="select_theme">
                                    <option value="" data-color="" data-color-two="">{l s='Theme 1' d='Shop.Theme.Global'}</option>
                                    <option value="theme2" data-color="#077f99" data-color-two="#ededed">{l s='Theme 2' d='Shop.Theme.Global'}</option>
                                    <option value="theme3" data-color="#456e54" data-color-two="#ededed">{l s='Theme 3' d='Shop.Theme.Global'}</option>
                                    <option value="theme4" data-color="#cf405e" data-color-two="#ededed">{l s='Theme 4' d='Shop.Theme.Global'}</option>
                                    <option value="theme_custom" data-color="">{l s='Custom' d='Shop.Theme.Global'}</option>
                                </select> *}
                                <div class="radio-toolbar tvselect-theme-select" id="select_theme">
                                    <input type="radio" id="radioTheme1" name="radioTheme" data-color="" data-color-two="" value="">
                                    <label for="radioTheme1" class="theme-1">
                                        <span>
                                            <svg class="primary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#d90244;" points="0,0 0,47 64.234,47 89.681,0  " />
                                                </g>
                                            </svg>
                                            <svg class="secondary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#ffd741;" points="89.681,0 25.445,0 0,47 89.681,47     " />
                                                </g>
                                            </svg>
                                        </span>
                                        <span class="tvtheme-label">{l s='Theme 1' d='Shop.Theme.Global'}</span>
                                    </label>
                                    <input type="radio" id="radioTheme2" name="radioTheme" data-color="#456e54" data-color-two="#ededed" value="theme2">
                                    <label for="radioTheme2" class="theme-2">
                                        <span>
                                            <svg class="primary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#456e54;" points="0,0 0,47 64.234,47 89.681,0  " />
                                                </g>
                                            </svg>
                                            <svg class="secondary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#ededed;" points="89.681,0 25.445,0 0,47 89.681,47     " />
                                                </g>
                                            </svg>
                                        </span>
                                        <span class="tvtheme-label">{l s='Theme 2' d='Shop.Theme.Global'}</span>
                                    </label>
                                    <input type="radio" id="radioTheme3" name="radioTheme" data-color="#cf405e" data-color-two="#ededed" value="theme3">
                                    <label for="radioTheme3" class="theme-3">
                                        <span>
                                            <svg class="primary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#cf405e;" points="0,0 0,47 64.234,47 89.681,0  " />
                                                </g>
                                            </svg>
                                            <svg class="secondary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#ededed;" points="89.681,0 25.445,0 0,47 89.681,47     " />
                                                </g>
                                            </svg>
                                        </span>
                                        <span class="tvtheme-label">{l s='Theme 3' d='Shop.Theme.Global'}</span>
                                    </label>
                                    <input type="radio" id="radioTheme4" name="radioTheme" data-color="#077f99" data-color-two="#ededed" value="theme4">
                                    <label for="radioTheme4" class="theme-4">
                                        <span>
                                            <svg class="primary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#077f99;" points="0,0 0,47 64.234,47 89.681,0  " />
                                                </g>
                                            </svg>
                                            <svg class="secondary-color" version="1.1" id="Layer_1" x="0px" y="0px" width="89.681px" height="47px" viewBox="0 0 89.681 47" style="enable-background:new 0 0 89.681 47;" xml:space="preserve">
                                                <g>
                                                    <polygon style="fill:#ededed;" points="89.681,0 25.445,0 0,47 89.681,47     " />
                                                </g>
                                            </svg>
                                        </span>
                                        <span class="tvtheme-label">{l s='Theme 4' d='Shop.Theme.Global'}</span>
                                    </label>
                                    <input type="radio" id="radioCustom" name="radioTheme" data-color="" value="theme_custom">
                                    <label for="radioCustom" class="theme-custom">
                                        <span>
                                            <i class="material-icons tvcolor-picker">&#xe23a;</i>
                                            Select Custom Color
                                            <i class="material-icons tvcolor-dropdown">&#xe5cf;</i>
                                        </span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr class="tvtheme-custom tvall-theme-content">
                            <td class="tvtheme-color-one">
                                <div class="tvcolor-theme-name">{l s='Primary Color' mod='tvcmsthemeoptions'}</div>
                                <div class="tvtheme-color-box">
                                    <input type="text" id="themecolor1" class="tvtheme-color-box-1" data-control="saturation" autocomplete="off">
                                    <label for="themecolor1">
                                        <span class="tvtheme-label">{l s='Primary Color' d='Shop.Theme.Global'}</span>
                                    </label>
                                </div>
                            </td>
                            <td class="tvtheme-color-two">
                                <div class="tvcolor-two-theme-name">{l s='Secondary Color' d='Shop.Theme.Global'}</div>
                                <div class="tvtheme-color-box">
                                    <input type="text" id="themecolor2" class="tvtheme-color-box-2" data-control="saturation" autocomplete="off">
                                    <label for="themecolor2">
                                        <span class="tvtheme-label">{l s='Secondary Color' d='Shop.Theme.Global'}</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr class="tvtheme-box-layout tvall-theme-content">
                            <td>
                                <div class="tvtheme-layout-name">{l s='Layouts' d='Shop.Theme.Global'}</div>
                                <div class="wide tvtheme-option">
                                    <input type="radio" id="wide-layout-toggle" class='tvtheme-wide-layout-option box-layout-option ' name="radio-group" checked="" value="wide" />
                                    <label for="wide-layout-toggle" class="tvtheme-option">{l s='Wide' d='Shop.Theme.Global'}</label>
                                </div>
                                <div class="box tvtheme-option">
                                    <input type="radio" id="box-layout-toggle" class='tvtheme-box-layout-option box-layout-option' name="radio-group" value="box" />
                                    <label for="box-layout-toggle" class="tvtheme-option">{l s='Box' d='Shop.Theme.Global'}</label>
                                </div>
                                <div class=" box-block">
                                    <div class="theme-switcher">
                                        <input type="radio" id="box-color-theme" name="box-themes" checked="" value="color">
                                        <label for="box-color-theme">
                                            <span>
                                                Color
                                            </span>
                                        </label>
                                        <input type="radio" id="box-pattern-theme" name="box-themes" value="pattern">
                                        <label for="box-pattern-theme">
                                            <span>
                                                Pattern
                                            </span>
                                        </label>
                                        <span class="slider"></span>
                                    </div>
                                    <div class="tvtheme-bgcolor-box open">
                                        <input type="text" id="themebgcolor2" data-control="saturation" class="tvtheme-bgcolor-box-2" autocomplete="off">
                                        <span id="tvtheme-reset-layout">
                                            <svg aria-hidden="true" role="img" width="20px" height="20px" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16">
                                                <g fill="currentColor">
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.75 8a4.5 4.5 0 0 1-8.61 1.834l-1.391.565A6.001 6.001 0 0 0 14.25 8A6 6 0 0 0 3.5 4.334V2.5H2v4l.75.75h3.5v-1.5H4.352A4.5 4.5 0 0 1 12.75 8z" />
                                                </g>
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="tvtheme-all-pattern-wrapper">
                                        {$i = 1}
                                        {while $i <= 21} <div class="tvtheme-all-pattern">
                                            <div id="pattern{$i}" class="tvtheme-pattern-image tvtheme-pattern-image{$i}" style="background-image:url('{$urls.img_url}pattern/pattern_sprite.png')" data-background-image-url="{$urls.img_url}pattern/pattern{$i}.png"></div>
                                    </div>
                                    {$i = $i + 1}
                                    {/while}
                                    <p class="notice">{l s='Custom background also available in admin.' mod='tvcmsthemeoptions'}</p>
                                </div>
                </div>
                </td>
                </tr>
                <tr class="tvtheme-background-layout tvall-theme-content">
                    <td>
                        <div class="tvtheme-layout-name">{l s='Body Background' d='Shop.Theme.Global'}</div>
                        <div class="bg-default tvtheme-option">
                            <input type="radio" id="bg-default-toggle" class='tvtheme-bg-default-option body-layout-option' name="bg-radio-group" checked="" value="default" />
                            <label for="bg-default-toggle" class="tvtheme-option">{l s='Default' d='Shop.Theme.Global'}</label>
                        </div>
                        <div class="bg-custom tvtheme-option">
                            <input type="radio" id="bg-custom-toggle" class='tvtheme-bg-custom-option body-layout-option' name="bg-radio-group" value="custom" />
                            <label for="bg-custom-toggle" class="tvtheme-option">{l s='Custom' d='Shop.Theme.Global'}</label>
                        </div>
                        <div class="bg-block">
                            <div class="theme-switcher">
                                <input type="radio" id="body-color-theme" name="body-themes" checked="" value="color">
                                <label for="body-color-theme">
                                    <span>
                                        Color
                                    </span>
                                </label>
                                <input type="radio" id="body-pattern-theme" name="body-themes" value="pattern">
                                <label for="body-pattern-theme">
                                    <span>
                                        Pattern
                                    </span>
                                </label>
                                <span class="slider"></span>
                            </div>
                            <div class="tvbody-bgcolor-wrapper tvtheme-body-bgcolor open">
                                {* <div class="tvbody-bgcolor-theme-name tvtheme-layout-name">{l s='Body Background Color' d='Shop.Theme.Global'}</div> *}
                                <input type="text" id="themebodybgcolor" class="tvtheme-bgcolor" data-control="saturation" autocomplete="on">
                                <span id="tvtheme-reset-bgcolor">
                                    <svg aria-hidden="true" role="img" width="20px" height="20px" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16">
                                        <g fill="currentColor">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.75 8a4.5 4.5 0 0 1-8.61 1.834l-1.391.565A6.001 6.001 0 0 0 14.25 8A6 6 0 0 0 3.5 4.334V2.5H2v4l.75.75h3.5v-1.5H4.352A4.5 4.5 0 0 1 12.75 8z"></path>
                                        </g>
                                    </svg>
                                </span>
                            </div>
                            <div class="tvtheme-body-background-patten">
                                {* <div class="tvtheme-body-background-pattern-name">{l s='Body Background Pattern' d='Shop.Theme.Global'}</div> *}
                                <div class="tvtheme-all-body-pattern-wrapper">
                                    {$i = 1}
                                    {while $i <= 21} <div class="tvtheme-all-body-pattern">
                                        <div id="bodypattern{$i}" class="tvtheme-body-pattern-image tvtheme-body-pattern-image{$i}" style="background-image:url('{$urls.img_url}pattern/pattern_sprite.png')" data-background-image-url="{$urls.img_url}pattern/pattern{$i}.png"></div>
                                </div>
                                {$i = $i + 1}
                                {/while}
                                <p class="notice">{l s='Custom background also available in admin.' mod='tvcmsthemeoptions'}</p>
                            </div>
                        </div>
        </div>
        </td>
        </tr>
        <tr class="tvtheme-theme-mode tvall-theme-content">
            <td>
                <div class="tvtheme-layout-name">{l s='IOS 13 Dark Mode' mod='tvcmsthemeoptions'}</div>
                <div class="tvtheme-theme-mode-wrapper">
                    <table>
                        <tr>
                            <td>
                                <div class="lightModeWrapper mode-option">
                                    <label for="TVCMSCUSTOMSETTING_LIHGT_MODE_INPUT">
                                        <img src="{$themeoptionsimagepath}light-icon.png" alt="Light Mode" loading="lazy" />
                                        <div class="tvcheck-popup"><i class='material-icons'>&#xe5ca</i></div>
                                        <input type="radio" id="TVCMSCUSTOMSETTING_LIHGT_MODE_INPUT" name="TVCMSCUSTOMSETTING_LIGHT_DARK_MODE_INPUT" value="0">
                                    </label>
                                    <p>
                                        <label for="TVCMSCUSTOMSETTING_LIHGT_MODE_INPUT">{l s='Light' mod='tvcmsthemeoptions'}</label><br />
                                    </p>
                                </div>
                            </td>
                            <td>
                                <div class="darkModeWrapper mode-option">
                                    <label for="TVCMSCUSTOMSETTING_DARK_MODE_INPUT">
                                        <img src="{$themeoptionsimagepath}dark-icon.png" alt="Dark Mode" loading="lazy" />
                                        <div class="tvcheck-popup"><i class='material-icons'>&#xe5ca</i></div>
                                        <input type="radio" id="TVCMSCUSTOMSETTING_DARK_MODE_INPUT" name="TVCMSCUSTOMSETTING_LIGHT_DARK_MODE_INPUT" value="1">
                                    </label>
                                    <p>
                                        <label for="TVCMSCUSTOMSETTING_DARK_MODE_INPUT">{l s='Dark' mod='tvcmsthemeoptions'}</label><br />
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <!-- 
                                <input type="checkbox" id="dark-mode" class='tvtheme-box-layout-option' />
                                <label for="dark-mode-layout" class="tvtheme-option-dark-mode"></label> -->
                </div>
            </td>
        </tr>
        <!-- <tr class="tvtheme-box-layout tvall-theme-content">
                    <td>
                        <div class="tvtheme-layout-name">{l s='Box-Layout' d='Shop.Theme.Global'}</div>
                        <div class="box tvtheme-option">
                            <input type="checkbox" id="box-layout-toggle" class='tvtheme-box-layout-option' />
                            <label for="box-layout-toggle" class="tvtheme-option">{* {l s='Toggle' d='Shop.Theme.Global'} *}</label>
                        </div>
                    </td>
                </tr> -->
        <!-- <tr class="tvtheme-body-bgcolor tvall-theme-content">
                    <td>
                        <div class="tvbody-bgcolor-theme-name tvtheme-layout-name">{l s='Body Background Color' d='Shop.Theme.Global'}</div>
                        <div class="tvtheme-color-box">
                            <input type="text" id="themebodybgcolor" class="tvtheme-bgcolor" data-control="saturation">
                        </div>
                    </td>
                </tr> -->
        <tr class="tvselect-title-font-1 tvall-theme-content">
            <td>
                <div class="tvselect-title-font-1-name tvtheme-layout-name">{l s='Title Font Family' d='Shop.Theme.Global'}</div>
                <select class="tvselect-title-font-1-select" id="select_title_font_1">
                    <option value="" data-font-link=''>{l s='Default Font Style' d='Shop.Theme.Global'}</option>
                    {foreach $title_font_list as $font}
                    <option value="{$font['name']}" data-font-link="{$font['link']}">{$font['name']}</option>
                    {/foreach}
                </select>
                <div class="tvtheme-title-color clearfix">
                    <div class="tvtheme-layout-name">{l s='Title Color' d='Shop.Theme.Global'}</div>
                    <div class="tvtheme-color-box">
                        <input type="text" id="themeCustomTitleColor" class="tvtheme-custom-title-color" data-control="saturation" autocomplete="off">
                        <label for="themeCustomTitleColor">
                            <span class="tvtheme-label">{l s='Title Color' d='Shop.Theme.Global'}</span>
                        </label>
                        <span id="tvtheme-reset-text">
                            <svg aria-hidden="true" role="img" width="20px" height="20px" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16">
                                <g fill="currentColor">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.75 8a4.5 4.5 0 0 1-8.61 1.834l-1.391.565A6.001 6.001 0 0 0 14.25 8A6 6 0 0 0 3.5 4.334V2.5H2v4l.75.75h3.5v-1.5H4.352A4.5 4.5 0 0 1 12.75 8z" />
                                </g>
                            </svg>
                        </span>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="tvselect-title-font-2 tvall-theme-content">
            <td>
                <div class="tvselect-title-font-2-name tvtheme-layout-name">{l s='Theme Font Family' d='Shop.Theme.Global'}</div>
                <select class="tvselect-title-font-2-select" id="select_title_font_2">
                    <option value="" data-font-link=''>{l s='Default Font Style' d='Shop.Theme.Global'}</option>
                    {foreach $title_font_list as $font}
                    <option value="{$font['name']}" data-font-link="{$font['link']}">{$font['name']}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr class="tvtheme-menu-sticky tvall-theme-content">
            <td>
                <div class="tvtheme-menu-sticky-name">
                    {l s='Menu Sticky' d='Shop.Theme.Global'}
                </div>
                {* <div class="box tvtheme-option">
                    <input type="checkbox" id="menu-sticky-toggle" class='tvtheme-menu-sticky-option' />
                    <label for="menu-sticky-toggle" class="tvtheme-option">{l s='Toggle' d='Shop.Theme.Global'}</label>
                </div> *}
                <div class="theme-switcher">
                    <input type="radio" id="menu-sticky" name="theme-menu" checked="" class='tvtheme-menu-sticky-option' value="sticky">
                    <label for="menu-sticky">
                        <span>
                            Sticky
                        </span>
                    </label>
                    <input type="radio" id="menu-no-sticky" name="theme-menu" class='tvtheme-menu-sticky-option' value="no-sticky">
                    <label for="menu-no-sticky">
                        <span class="sticky-menu">
                            No Sticky
                        </span>
                    </label>
                    <span class="slider"></span>
                </div>
            </td>
        </tr>
        </table>
        <button class="tvtheme-control-reset tvall-inner-btn">
            <span>{l s='Reset' d='Shop.Theme.Global'}</span>
        </button>
    </div>
    <div class="tvtheme-control-icon">
        {* <i class='material-icons'>&#xf05e;</i> *}
        <i class='material-icons'>settings_suggest</i>
    </div>
    </form>
    </div>
    </div>
    {/if}
    {/strip}