# Program
Program content type is used for adding Programs on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the program item. |
| **Header Area** | Field group |||
| Icon | field\_program_icon | No | A image field, supporting .svg for uploading the program icon. |
| Image | field\_program_image | No | A image field, for uploading the program image. |
| Color | field\_program_color | No | Reference field for choosing the term from "Color" vocabulary. |
| **Content Area** | Field group |||
| Description | field\_program_description | No | Textarea for the description/body with WYSIWYG, without summary. |
| Content | field_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Sidebar Area** | Field group |||
| Content | field\_sidebar_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |

### URL pattern

Content type is using following pattern:
`/programs/[node:title]`
