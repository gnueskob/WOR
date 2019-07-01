<?php

// 파일 정보 출력
?>
<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>sample page</title>
    </head>
    <body>
        <form action="./upload.php" method="post" enctype="multipart/form-data">
            <fieldset>
                영토 <input type="file" name="territory" id="territory" /> <br/>
                타일 <input type="file" name="tile" id="tile" /> <br/>
                자원 <input type="file" name="resource" id="resource" /> <br/>
                건물 <input type="file" name="building" id="building" /> <br/>
                무기 <input type="file" name="weapon" id="weapon" /> <br/>
                버프 <input type="file" name="buff" id="buff" /> <br/>
                전리품 <input type="file" name="trophy" id="trophy" /> <br/>
                단위 <input type="file" name="unit" id="unit" /> <br/>
                <input type="submit" name="upload" value="Upload" />
            </fieldset>
        </form>
    </body>
</html>

