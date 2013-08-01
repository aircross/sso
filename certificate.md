--生成私钥
openssl genrsa -out ras_private_key.key 1024
openssl rsa -in ras_private_key.key -out ras_private_key.pem
--生成证书请求文件
openssl req -new -key ras_private_key.key -out certificate_request.csr
--生成证书
openssl x509 -req -days 9999 -in certificate_request.csr -signkey ras_private_key.key -out certificate.crt

openssl req -new -x509 -key ras_private_key.pem -out one_step_certificate.pem
