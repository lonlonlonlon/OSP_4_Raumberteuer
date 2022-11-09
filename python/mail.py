import smtplib, ssl
import sys
from email import encoders
from email.mime.base import MIMEBase
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

# ...
subject = sys.argv[2]
body = sys.argv[3]
sender_email = 'raumbetreuer.tool@gmail.com'
receiver_email = sys.argv[1]
password='wsrfzpdgygylquen'
# file_attatchment_name = 'getpip.py'
#
# # Create a multipart message and set headers
message = MIMEMultipart()
message['From'] = 'Raumbetreuer Tool'
message['To'] = receiver_email
message['Subject'] = subject
# # Add body to email
message.attach(MIMEText(body, 'plain'))
# # Open file in binary mode
# with open( file_attatchment_name, 'rb') as attachment:
#     # Add file as application/octet-stream
#     # Email client can usually download this automatically as attachment
#     part = MIMEBase('application', 'octet-stream')
#     part.set_payload(attachment.read())
# # Encode file in ASCII characters to send by email
# encoders.encode_base64(part)
# # Add header as key/value pair to attachment part
# part.add_header(
#     'Content-Disposition',
#     f'attachment; filename={subject}',
# )
# # Add attachment to message and convert message to string
# message.attach(part)
text = message.as_string()
# Log in to server using secure context and send email
context = ssl.create_default_context()
with smtplib.SMTP_SSL('smtp.gmail.com', 465, context=context) as server:
    server.login(sender_email, password)
    server.sendmail(sender_email, receiver_email, text)
print( 'OK')