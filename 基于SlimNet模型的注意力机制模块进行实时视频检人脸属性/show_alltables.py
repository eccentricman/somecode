from src import correct_face_orientation
import onnxruntime
from src import detect_faces
import cv2
import numpy as np
from PIL import Image
import time

def cv2_preprocess(img): #numpy预处理和torch处理一样
    img = cv2.resize(img, (178, 218), interpolation=cv2.INTER_LANCZOS4)
    img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    mean = [0.5,0.5,0.5] #一定要是3个通道，不能直接减0.5
    std = [0.5,0.5,0.5]
    img = ((img / 255.0 - mean) / std)
    img = img.transpose((2,0,1)) #hwc变为chw
    img = np.expand_dims(img, axis=0) #3维到4维
    img = np.ascontiguousarray(img, dtype=np.float32) #转换浮点型
    return img
def process_face(x1, y1, x2, y2,img_w,img_h):
    w, h = x2 - x1, y2 - y1
    ratio = 0.2
    x1 = int(x1 - w * ratio)
    x2 = int(x2 + w * ratio)
    y1 = int(y1 - h * (ratio+0.1))
    y2 = int(y2 + h * (ratio+0.1))
    if x1<0:
        x1=0
    if y1<0:
        y1=0
    if x2>img_w:
        x2=img_w
    if y2>img_h:
        y2=img_h
    return x1, y1, x2, y2

def show_facestables(image, bounding_boxes, facial_landmarks=[],list_attr_cn=None,ort_session=None):
    image = np.array(image)
    h,w = image.shape[:2]
    result_image=image.copy()
    for i in range(bounding_boxes.shape[0]):
        x1, y1, x2, y2, _ = bounding_boxes[i].astype(int)
        x1, y1, x2, y2 = process_face(x1, y1, x2, y2,w,h)
        face = image[x1:x2, y1:y2]
        if(face.shape[0]<=0 or face.shape[1]<=0):
            return result_image
#        points = []
#        for j in range(5):
#        # 提取关键点的x和y坐标
#            x = facial_landmarks[i][j]-x_left
#            y = facial_landmarks[i][j + 5]-y_up
#        # 创建一个元组，包含关键点的矩形坐标
#            point = (x,y)
#            # 将关键点矩形坐标添加到列表中
#            points.append(point)
#        face=correct_face_orientation(face,points) 
        x = cv2_preprocess(face)
        ort_inputs = {ort_session.get_inputs()[0].name: x}
        ort_outs = ort_session.run(None, ort_inputs)
        possibility = (ort_outs[0]) > 0.5
        result = list_attr_en[possibility[0]]
        cv2.rectangle(result_image, (x1, y1), (x2, y2), (255,255,255), 1)
        for i in range(0,len(result)):
            result_image = cv2.putText(result_image, result[i],(x1,y1+i*20+20), cv2.FONT_HERSHEY_SIMPLEX, 
                  0.5, (255,255,255), 1, cv2.LINE_AA)
    return result_image
#image = Image.open('images/godness6.jpg')
#bounding_boxes, landmarks = detect_faces(image)
#show_facestables(image,bounding_boxes,landmarks,list_attr_cn)
# 用于计算帧率的变量


list_attr_en = np.array(["5_o_Clock_Shadow","Arched_Eyebrows","Attractive","Bags_Under_Eyes","Bald",
"Bangs","Big_Lips","Big_Nose","Black_Hair","Blond_Hair","Blurry","Brown_Hair",
"Bushy_Eyebrows","Chubby","Double_Chin","Eyeglasses","Goatee","Gray_Hair",
"Heavy_Makeup","High_Cheekbones","Male","Mouth_Slightly_Open","Mustache","Narrow_Eyes",
"No_Beard","Oval_Face","Pale_Skin","Pointy_Nose","Receding_Hairline","Rosy_Cheeks",
"Sideburns","Smiling","Straight_Hair","Wavy_Hair","Wearing_Earrings","Wearing_Hat",
"Wearing_Lipstick","Wearing_Necklace","Wearing_Necktie","Young"])
ort_session = onnxruntime.InferenceSession(r"cpu.onnx", providers=['CPUExecutionProvider'])

cap = cv2.VideoCapture(0)
# 检查视频是否成功打开
if not cap.isOpened():
    print("Error: 无法打开视频文件")
    exit()
# 逐帧读取视频
while cap.isOpened():
    ret, frame = cap.read()
    if ret:
        # 在这里处理每一帧，例如显示、分析等
        start_time = time.time()
        frame = Image.fromarray(frame)
        bounding_boxes, landmarks = detect_faces(frame)
        end_time = time.time()
        print(end_time-start_time)
        # 确保bounding_boxes是NumPy数组
        if not isinstance(bounding_boxes, np.ndarray):
            continue
        frame = show_facestables(frame, bounding_boxes, landmarks, list_attr_en,ort_session)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break
        cv2.imshow('Frame', frame)
    else:
        break
# 释放视频捕获对象
cap.release()
# 关闭所有OpenCV窗口
cv2.destroyAllWindows()
#pic = cv2.imread("./images/NewJeans.jpg")
#pic = Image.fromarray(pic)
#bounding_boxes, landmarks = detect_faces(pic)
#pic = show_facestables(pic, bounding_boxes, landmarks, list_attr_en,ort_session)
#cv2.imshow('Frame', pic)
