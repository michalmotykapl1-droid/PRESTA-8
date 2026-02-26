{* --- CZYSTA LISTA DLA LEWEJ KOLUMNY (PL + FULL WIDTH BUTTON) --- *}
{if isset($posts) && $posts}
    <div class="block ets_block_latest {$ETS_BLOG_RTL_CLASS|escape:'html':'UTF-8'} page_blog">
        
        {* NAGŁÓWEK PO POLSKU *}
        <h4 class="title_blog title_block" style="text-transform: uppercase; font-weight: 700; margin-bottom: 20px; color: #333;">
            NAJNOWSZE WPISY
        </h4>
        
        <div class="block_content">
            <ul class="ets-sidebar-post-list" style="list-style: none; padding: 0; margin: 0;">
                
                {foreach from=$posts item='post' name='blog_posts'}
                    {* Limit: 4 posty w sidebarze *}
                    {if $smarty.foreach.blog_posts.iteration <= 4}
                    
                    <li style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid #f5f5f5; padding-bottom: 15px;">
                        
                        {* Mały obrazek *}
                        {if $post.thumb}
                            <a class="ets_item_img" href="{$post.link|escape:'html':'UTF-8'}" style="flex: 0 0 80px; width: 80px; height: 60px; overflow: hidden; border-radius: 6px;">
                                <img src="{$post.thumb|escape:'html':'UTF-8'}" alt="{$post.title|escape:'html':'UTF-8'}" style="width: 100%; height: 100%; object-fit: cover;" />
                            </a>
                        {/if}
                        
                        {* Tytuł i Data *}
                        <div class="ets-blog-latest-post-content" style="flex: 1;">
                            <h5 style="margin: 0 0 5px 0; line-height: 1.3;">
                                <a href="{$post.link|escape:'html':'UTF-8'}" style="font-size: 13px; font-weight: 600; color: #333; text-decoration: none; transition: color 0.2s;">
                                    {$post.title|escape:'html':'UTF-8'|truncate:45:'...'}
                                </a>
                            </h5>
                            <span class="post-date" style="font-size: 11px; color: #999; display: block;">
                                <i class="fa fa-clock-o"></i> {dateFormat date=$post.date_add full=0}
                            </span>
                        </div>
                    </li>
                    
                    {/if}
                {/foreach}
                
            </ul>
            
            {* PRZYCISK NA PEŁNĄ SZEROKOŚĆ PO POLSKU *}
            <div class="blog_view_all_button" style="margin-top: 10px;">
                <a href="{if isset($latest_link)}{$latest_link|escape:'html':'UTF-8'}{else}#{/if}" 
                   class="view_all_link" 
                   style="
                       display: block;             /* Rozciąga na całą linię */
                       width: 100%;                /* Pełna szerokość kontenera */
                       text-align: center;         /* Tekst na środku */
                       background-color: #2fb5d2;  /* Kolor niebieski (jak na foto) - zmień na #d32f2f jeśli chcesz czerwony */
                       color: #fff;
                       padding: 12px 0;            /* Wysoki przycisk */
                       font-size: 12px;
                       font-weight: 700;
                       text-transform: uppercase;
                       text-decoration: none;
                       border-radius: 4px;
                       transition: background 0.3s;
                   "
                   onmouseover="this.style.backgroundColor='#2592a9'" 
                   onmouseout="this.style.backgroundColor='#2fb5d2'"
                >
                    ZOBACZ WSZYSTKIE <i class="fa fa-angle-right" style="margin-left:5px;"></i>
                </a>
            </div>
        </div>
    </div>
{/if}