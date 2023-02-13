# wp-boycott-douban-db

在Wordpress本地保存书籍、电影、游戏、音乐专辑信息，并以单幅或列表的形式展示出来。

* Tags: infinite scroll, ajax, book, movie, game, album, personal collection, douban, imdb
* Requires at least: 4.4
* Requires PHP: 5.6
* Tested up to: 4.10
* Stable tag: 4.9.2
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

## 功能

* 后台保存电影、书籍、游戏、唱片信息
* 插入到文章中显示
* 后台半自动从豆瓣、imdb等网站抓取必要的信息。
* 自建页面以图片墙的形式显示保存的资源
* 目前对游戏（game）、专辑（album）的支持尚不完全。

### 在文章中插入娱乐信息

* 在文章或页面中插入如下短代码:  
  `[bddbr id='444' /]`

### 在主题中加入信息墙页面

* 在主题中创建[页面模板](https://developer.wordpress.org/themes/template-files-section/page-template-files/)
* 修改页面模板,在适当的位置调用如下代码:  
  `bddb_the_gallery('book'); //book, movie, game, album`
* 使用模板创建空页面（page）

### 抓取信息
* 后台“外部链接”输入豆瓣页面后，点击后面的按钮，可以从豆瓣网抓取对应的内容（不包括游戏）直接填入。海报图片不会自动抓取，需要在“图片链接”处再点击一次取得。
* 电影信息支持通过omdb的API抓取imdb信息，用以对付一些豆瓣没有的条目。该API Key可以去[omdbapi官网](https://www.omdbapi.com) 申请。后台填入后即可使用。

### 书籍信息项目

注: 数字字符串表示显示顺序或者查询时排序顺序，▲表示默认未加但支持手动配置修改，⚪表示默认显示到固定位置，×表示不建议改动。下同。

| 意义    | 字段名             | 字段类型      | 列表排序 | 排序方法 | 列表详情位置 | 单幅位置 | 特殊处理                    |
|:-----:|:--------------- |:---------:|:----:|:----:|:------:|:----:|:-----------------------:|
| 书名    | display_name    | 字符串       | 10   | ASC  | ⚪      | ⚪    | -                       |
| 原名    | original_name   | 字符串       | ▲    | -    | 01     | 01   | 与书名不同时显示                |
| 别名    | aka             | 字符串       | ▲    | -    | ▲      | ▲    | -                       |
| 简评    | personal_review | 字符串       | ×    | -    | ⚪      | ⚪    | -                       |
| 外链    | external_link   | 字符串       | ×    | -    | ⚪      | ⚪    | -                       |
| 图片链接  | poster_link     | 字符串       | ×    | -    | ×      | ×    | 后台用                     |
| 出版时间  | publish_time    | 字符串       | 03   | DESC | 02     | 21   | 可能与最终出版时间联动             |
| 阅读时间  | view_time       | 字符串       | 02   | ASC  | 03     | ▲    | 只显示年                    |
| 评分    | personal_rating | 数字(0-100) | 01   | DESC | ⚪      | ⚪    | -                       |
| 地区    | b_region        | 枚举        | ▲    | -    | ▲      | ▲    | -                       |
| 作者    | b_p_writer      | 枚举        | ▲    | -    | 01     | 02   | -                       |
| 译者    | b_p_translator  | 枚举        | ▲    | -    | 12     | 03   | -                       |
| 编者    | b_p_editor      | 枚举        | ▲    | -    | 13     | ▲    | -                       |
| 分类    | b_genre         | 枚举        | ▲    | -    | 04     | ▲    | -                       |
| 出版社   | b_publisher     | 枚举        | ▲    | -    | 20     | 05   | -                       |
| 全套册数  | b_series_total  | 数字        | ×    | -    | 05     | 31   | -                       |
| 豆瓣ID  | id_douban       | 字符串       | ▲    | -    | ▲      | ▲    | -                       |
| 豆瓣评分  | score_douban    | 字符串       | ▲    | -    | ⚪      | ⚪    | -                       |
| 特殊标志  | b_misc_brand    | 枚举        | ▲    | -    | ⚪      | ⚪    | -        图标显示。详见QA No.4 |
| 系列标志  | b_bl_series     | 布尔        | ×    | -    | ×      | ×    | 特殊用途，不建议修改              |
| 最终出版日 | b_pub_time_end  | 字符串       | ×    | -    | ⚪      | ⚪    | 与出版日配合显示，不单独显示          |
| 封面们   | b_series_covers | 字符串       | ×    | -    | ×      | ×    | 后台字段，不建议修改              |

### 电影信息项目

| 意义     | 字段名              | 字段类型      | 列表排序 | 排序方法 | 列表详情位置 | 单幅位置 | 特殊处理                    |
|:------:|:---------------- |:---------:|:----:|:----:|:------:|:----:|:-----------------------:|
| 电影名    | display_name     | 字符串       | 10   | ASC  | ⚪      | ⚪    | -                       |
| 原名     | original_name    | 字符串       | ▲    | -    | 01     | 01   | 外片时显示                   |
| 别名     | aka              | 字符串       | ▲    | -    | ▲      | ▲    | -                       |
| 简评     | personal_review  | 字符串       | ×    | -    | ⚪      | ⚪    | -                       |
| 外链     | external_link    | 字符串       | ×    | -    | ⚪      | ⚪    | -                       |
| 图片链接   | poster_link      | 字符串       | ×    | -    | ×      | ×    | 后台用                     |
| 首映时间   | publish_time     | 字符串       | 03   | DESC | 02     | 21   | -                       |
| 观看时间   | view_time        | 字符串       | 02   | ASC  | 03     | ▲    | 只显示年                    |
| 评分     | personal_rating  | 数字(0-100) | 01   | DESC | ⚪      | ⚪    | -                       |
| 地区     | m_region         | 枚举        | ▲    | -    | 05     | 11   | -                       |
| 导演     | m_p_director     | 枚举        | ▲    | -    | 11     | 02   | -                       |
| 主演     | m_p_actor        | 枚举        | ▲    | -    | 12     | 03   | -                       |
| 类型     | m_genre          | 枚举        | ▲    | -    | 04     | 04   | -                       |
| 出品方    | m_publisher      | 枚举        | ▲    | -    | ▲      | ▲    | -                       |
| 编剧     | m_p_screenwriter | 枚举        | ▲    | -    | ▲      | ▲    | -                       |
| 配乐     | m_p_musician     | 枚举        | ▲    | -    | ▲      | ▲    | -                       |
| 特殊标志   | m_misc_brand     | 枚举        | ▲    | -    | ⚪      | ⚪    | -        图标显示。详见QA No.4 |
| 豆瓣ID   | id_douban        | 字符串       | ▲    | -    | ▲      | ▲    | -                       |
| 豆瓣评分   | score_douban     | 字符串       | ▲    | -    | ⚪      | ⚪    | -                       |
| IMDB编号 | m_id_imdb        | 字符串       | ▲    | -    | ▲      | ▲    | -                       |
| IMDB评分 | m_score_imdb     | 字符串       | ▲    | -    | ⚪      | ⚪    | -                       |

### 游戏信息项目

### 专辑信息项目

## 安装

* 解压缩后上传至wordpress/wp-content/plugins/目录下
* 激活插件
* 进入wordpress后台设置画面进行设置

## 功能限制

* 在Wordpress4.9.X下测试通过，Wordpress 5.X下未测试效果。
* “插入文章显示”功能仅限短代码方式。
* 激活插件后，将创建“book，movie，game，album”四个post种类，卸载插件后不会删除。（删除功能未支持）
* 每条信息将向wp_posts表中插入1条记录，向wp_postmeta，wp_terms，wp_term_relationships中追加n条记录，在指定的图片缓存路径下创建1张海报和1张海报缩略图。（删除功能未支持）

## Q&A

1. **如何调整海报列表中所有信息的显示顺序？**
   
   - 目前默认的数据库检索顺序为:个人评分D，接触时间A，出品时间A，作品名A。如果想调整，可以参照文档前面的项目信息，修改class-bddb-templates.php中对应项目的priority和sort的设定值。
     
     - 【例】修改图书列表显示顺序，改为阅读时间D，豆瓣评分D，作者A：
       修改add_book_items函数。
       
       ```php
       private function add_book_items() {
            //...
               $this->common_items['bddb_view_time']['panel_callback'] = array($this, 'panel_time_only_year');
            //开始追加1
            $this->common_items['bddb_view_time']['priority'] = "01";
            $this->common_items['bddb_view_time']['sort'] = "DESC";
            $this->common_items['bddb_publish_time']['priority'] = false;
            $this->common_items['bddb_personal_rating']['priority'] = false;
            //结束追加1
            //...
                   'b_p_writer' => array(    'name' => 'b_p_writer',
                                                   'label' => '作者',
                                                   'type' => 'tax',
                                                   'summary' => '02',
                                                   'panel' => '01',            
                                                //开始追加2
                                                'priority' => '03',
                                                'sort' => 'ASC',
                                                //结束追加2
                                                   ),
                //...
                'bddb_score_douban' => array(    'name' => 'bddb_score_douban',
                                            'label' => '豆瓣评分',
                                            //开始追加3
                                            'priority' => '02',
                                            'sort' => 'DESC',
                                            //结束追加3
                                            ),
       //...
       ```

2. **如何调整图片列表详情的内容和显示顺序？**
   
   - 如果想调整，可以参照文档前面的项目信息，修改如果想调整，可以参照文档前面的项目信息，修改class-bddb-templates.php中对应项目的panel的设定值。
     
     - 【例】修改电影列表显示详情，将别名显示在原名之后,将编剧显示在导演之后：
       修改add_movie_items函数。   
       
       ```php
       private function add_movie_items() {
        //...
        $this->common_items['bddb_publish_time']['summary_callback'] = array($this, 'display_movie_publish_time');
        //开始追加1
        $this->common_items['bddb_aka']['panel'] = "02";
        //结束追加1
        $add_items = array(
            //...
            'm_p_screenwriter' => array(    'name' => 'm_p_screenwriter',
                                            'label' => '编剧',
                                            'type' => 'tax',
                                            //开始追加2
                                            'panel' => '02',
                                            //结束追加2
                                            ),
       //...
       ```

3. **如何单幅信息显示的内容和显示顺序？**
   
   - 如果想调整，可以参照文档前面的项目信息，修改class-bddb-templates.php中对应项目的summary的设定值。
     
     - 【例】修改电影单幅显示内容，去掉原名，将配乐显示在导演之后：
       修改add_movie_items函数。   
       
       ```php
       private function add_movie_items() {
        //...
        $this->common_items['bddb_publish_time']['summary_callback'] = array($this, 'display_movie_publish_time');
        //开始追加1
        $this->common_items['bddb_original_name']['summary'] = false;
        //结束追加1
        $add_items = array(
            //...
            'm_p_screenwriter' => array(    'name' => 'm_p_musician',
                                            'label' => '配乐',
                                            'type' => 'tax',
                                            //开始追加2
                                            'panel' => '02',
                                            //结束追加2
                                            ),
       //...
       ```

4. **如何使用特记图片功能？**
   
   - 选中后台Book/Movie/Game/Album菜单对应的Brands子菜单，添加或者编辑tag。把tag的slug修改成特定的文字（建议用英文），然后在/img/路径下添加与slug同名的图片文件。完成后列表详细信息和单幅信息中会显示特定的图标。
     - 例：在Movie->Brands中新建一个tag，Name为“禁片”，slug为“404”。然后在/img/路径下添加一个404.png的文件。编辑电影“颐和园”条目，“特殊头衔”栏输入禁片标签，保存。则条目“颐和园”出现时会在最下一行显示404.png所对应的图片。

5. **为什么四种信息的地区用了四个不同的字段记录，而不是使用同一个字段？**
   
   - 后台统计条目的时候，会按照相同的字段跨类型统计，这样在后台列表上第一眼看到的数据就不准确。
   - 不仅地区，出版发行方信息和类别也都用了4个不同的字段。

6. **如何使用图床缓存海报和封面？**
   
   - 请自行研究。作者不喜欢图床，不会开发任何与图床或CDN有关的功能。

7. **古腾堡编辑器下如何插入信息？**
   
   - 请自行研究，作者不愿意支持古腾堡编辑器。