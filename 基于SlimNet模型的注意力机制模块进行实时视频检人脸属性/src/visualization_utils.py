from PIL import ImageDraw
import cv2
import numpy as np
import matplotlib.pyplot as plt
def show_bboxes(img, bounding_boxes, facial_landmarks=[]):
    """Draw bounding boxes and facial landmarks.

    Arguments:
        img: an instance of PIL.Image.
        bounding_boxes: a float numpy array of shape [n, 5].
        facial_landmarks: a float numpy array of shape [n, 10].

    Returns:
        an instance of PIL.Image.
    """

    img_copy = img.copy()
    draw = ImageDraw.Draw(img_copy)

    for b in bounding_boxes:
        draw.rectangle([
            (b[0], b[1]), (b[2], b[3])
        ], outline='white')

    for p in facial_landmarks:
        for i in range(5):
            draw.ellipse([
                (p[i] - 1.0, p[i + 5] - 1.0),
                (p[i] + 1.0, p[i + 5] + 1.0)
            ], outline='blue')

    return img_copy
def crop_and_show_faces(image, bounding_boxes, facial_landmarks=[]):
    image1=show_bboxes(image,bounding_boxes,facial_landmarks)
    
    image = np.array(image1)
    
#    image = cv2.cvtColor(np_image, cv2.COLOR_RGB2BGR)
    
    for i in range(bounding_boxes.shape[0]):
        x1, y1, x2, y2, _ = bounding_boxes[i].astype(int)
        face = image[y1:y2, x1:x2]
        points = []
        for j in range(5):
        # 提取关键点的x和y坐标
            x = facial_landmarks[i][j]-x1
            y = facial_landmarks[i][j + 5]-y1
        # 创建一个元组，包含关键点的矩形坐标
            point = (x,y)
            # 将关键点矩形坐标添加到列表中
            points.append(point)
        face=correct_face_orientation(face,points) 
        face=cv2.resize(face,(48,48))
        print(face.shape[0],face.shape[1])
        plt.subplot(4, 5, i+1)
        plt.imshow(face, cmap='gray')
        plt.title(f'Image {i+1}')
# 显示重构的人脸
    plt.suptitle('all reized_correct_face', fontsize=16)
    plt.show()
    cv2.waitKey(0)
    cv2.destroyAllWindows()


def calculate_rotation_angle(eye_left, eye_right, mouth_left, mouth_right):
    # Calculate the center of the eyes and mouth
    eye_center = ((eye_left[0] + eye_right[0]) / 2, (eye_left[1] + eye_right[1]) / 2)
    mouth_center = ((mouth_left[0] + mouth_right[0]) / 2, (mouth_left[1] + mouth_right[1]) / 2)

    # Calculate the rotation angle
    delta_y = eye_center[1] - mouth_center[1]
    delta_x = eye_center[0] - mouth_center[0]
    angle = np.degrees(np.arctan2(delta_y, delta_x))

    return angle+90

def correct_face_orientation(image, facial_landmarks):
    # Extract facial landmarks
    eye_left, eye_right, nose, mouth_left, mouth_right = facial_landmarks
    # Calculate rotation angle
    angle = calculate_rotation_angle(eye_left, eye_right, mouth_left, mouth_right)
    # Calculate rotation matrix
    h, w = image.shape[:2]
    rotation_matrix = cv2.getRotationMatrix2D((w / 2, h / 2), angle, 1)
    # Apply rotation
    
    rotated_image = cv2.warpAffine(image, rotation_matrix, (w, h))

    return rotated_image



