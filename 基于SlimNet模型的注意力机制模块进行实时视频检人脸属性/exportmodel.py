import torch
from NET.net1 import SlimNet
import numpy as np
import onnxruntime
import torch.onnx

torch_model = SlimNet(40)
torch_model = torch.load("model_resnet50-76.10798016230838.pt", map_location='cpu')
torch_model.eval()
x = torch.randn(1, 3, 128, 128, requires_grad=True) #随机128*128输入
torch_out = torch_model(x)
print(torch_out)
# 导出模型
torch.onnx.export(torch_model,               # 需要导出的模型
                  x,                         # 模型输入
                  "resnet-50.onnx",   # 保存模型位置
                  export_params=True,        # 保存训练参数
                  opset_version=10,          # onnx的opset版本
                  do_constant_folding=True,  # 是否进行常量折叠优化，这里开关都一样
                  input_names = ['input'],   # 输入名字
                  output_names = ['output'], # 输出名字
                  )

 
# 创建一个推理会话
session = onnxruntime.InferenceSession(r"resnet-50.onnx", providers=['CPUExecutionProvider'])
 
#尝试进行推理看是否报错
def to_numpy(tensor):
    return tensor.detach().cpu().numpy() if tensor.requires_grad else tensor.cpu().numpy()

ort_inputs = {session.get_inputs()[0].name: to_numpy(x)}
ort_outs = session.run(None, ort_inputs)
print(ort_outs[0])
# 比较onnx模型推理的结果和torch推理的结果误差是否在可容忍范围内
np.testing.assert_allclose(to_numpy(torch_out), ort_outs[0], rtol=1e-03, atol=1e-05)

print("Exported model has been tested with ONNXRuntime, and the result looks good!")
